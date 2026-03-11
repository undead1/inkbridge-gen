<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Inkbridge_Gen_Pipeline {

	private $generator;
	private $translator;
	private $publisher;
	private $image_handler;
	private $settings;

	public function __construct(
		Inkbridge_Gen_Generator $generator,
		Inkbridge_Gen_Translator $translator,
		Inkbridge_Gen_Publisher $publisher,
		?Inkbridge_Gen_Image_Handler $image_handler,
		Inkbridge_Gen_Settings $settings
	) {
		$this->generator     = $generator;
		$this->translator    = $translator;
		$this->publisher     = $publisher;
		$this->image_handler = $image_handler;
		$this->settings      = $settings;
	}

	/**
	 * Run the full pipeline for a single topic.
	 */
	public function run( string $topic, string $pillar, array $options = array() ): array {
		$word_count     = $options['word_count'] ?? 0;
		$extra_context  = $options['extra_context'] ?? '';
		$skip_translate = $options['skip_translate'] ?? false;
		$skip_image     = $options['skip_image'] ?? false;
		$languages      = $options['languages'] ?? array();
		$status         = $options['status'] ?? $this->settings->get( 'default_post_status', 'draft' );

		$result = array(
			'success'     => false,
			'topic'       => $topic,
			'pillar'      => $pillar,
			'articles'    => array(),
			'image'       => null,
			'posts'       => array(),
			'errors'      => array(),
			'token_usage' => array( 'total_input' => 0, 'total_output' => 0 ),
		);

		// Step 1: Generate source article.
		try {
			$article = $this->generator->generate( $topic, $pillar, $word_count, $extra_context );
			$source_lang = $article['language'];
			$result['articles'][ $source_lang ] = $article;
		} catch ( \RuntimeException $e ) {
			$result['errors']['generation'] = $e->getMessage();
			return $result;
		}

		// Step 2: Translate.
		if ( ! $skip_translate ) {
			$target_langs = $languages;
			if ( empty( $target_langs ) ) {
				$target_langs = array_map( fn( $l ) => $l['code'], $this->settings->get_target_languages() );
			}
			// Remove source language from targets.
			$target_langs = array_diff( $target_langs, array( $source_lang ) );

			if ( ! empty( $target_langs ) ) {
				$translations = $this->translator->translate_to_all( $article, array_values( $target_langs ) );
				foreach ( $translations as $lang => $translated ) {
					if ( $lang === $source_lang ) {
						continue;
					}
					if ( isset( $translated['error'] ) ) {
						$result['errors'][ "translate_{$lang}" ] = $translated['error'];
					} else {
						$result['articles'][ $lang ] = $translated;
					}
				}
			}
		}

		// Step 3: Fetch image.
		$featured_image_id = 0;
		if ( ! $skip_image && $this->image_handler ) {
			$featured_image_id = $this->image_handler->fetch_and_attach( $topic, $article['title'] ?? $topic );
			if ( $featured_image_id ) {
				$result['image'] = array(
					'attachment_id' => $featured_image_id,
					'url'           => wp_get_attachment_url( $featured_image_id ),
				);
			}
		}

		// Step 4: Publish all versions.
		$publish_result = $this->publisher->publish_multilingual(
			$result['articles'],
			$featured_image_id,
			array( 'status' => $status )
		);

		$result['posts']  = $publish_result['posts'];
		$result['errors'] = array_merge( $result['errors'], $publish_result['errors'] ?? array() );
		$result['success'] = ! empty( $result['posts'] );

		return $result;
	}

	/**
	 * Execute a single step of the pipeline (for step-by-step AJAX).
	 */
	public function run_step( string $step, array $data ): array {
		switch ( $step ) {
			case 'generate':
				$article = $this->generator->generate(
					$data['topic'],
					$data['pillar'],
					$data['word_count'] ?? 0,
					$data['extra_context'] ?? ''
				);
				return array( 'success' => true, 'article' => $article );

			case 'translate':
				$translated = $this->translator->translate( $data['article'], $data['language'] );
				return array( 'success' => true, 'article' => $translated );

			case 'image':
				if ( ! $this->image_handler ) {
					return array( 'success' => true, 'attachment_id' => 0 );
				}
				$attachment_id = $this->image_handler->fetch_and_attach(
					$data['query'] ?? $data['topic'] ?? '',
					$data['title'] ?? ''
				);
				return array(
					'success'       => true,
					'attachment_id' => $attachment_id,
					'url'           => $attachment_id ? wp_get_attachment_url( $attachment_id ) : '',
				);

			case 'publish':
				if ( empty( $data['articles'] ) || ! is_array( $data['articles'] ) ) {
					throw new \RuntimeException( 'No articles to publish.' );
				}
				$result = $this->publisher->publish_multilingual(
					$data['articles'],
					$data['featured_image_id'] ?? 0,
					array( 'status' => $data['status'] ?? 'draft' )
				);
				if ( empty( $result['posts'] ) ) {
					$error_msg = 'Failed to create posts.';
					if ( ! empty( $result['errors'] ) ) {
						$error_msg .= ' ' . implode( '; ', $result['errors'] );
					}
					throw new \RuntimeException( $error_msg );
				}
				return array( 'success' => true, 'result' => $result );

			default:
				throw new \RuntimeException( "Unknown pipeline step: {$step}" );
		}
	}

	/**
	 * Build a pipeline instance from settings.
	 */
	public static function from_settings( ?Inkbridge_Gen_Settings $settings = null, string $text_provider_id = '', string $image_provider_id = '' ): self {
		if ( ! $settings ) {
			$settings = new Inkbridge_Gen_Settings();
		}

		$db     = new Inkbridge_Gen_DB();
		$logger = new Inkbridge_Gen_Logger( $db );

		// Text provider.
		$tp_id  = $text_provider_id ?: $settings->get_active_text_provider();
		$tp_key = $settings->get_provider_api_key( $tp_id );
		$tp_cfg = $settings->get_text_provider_config( $tp_id );
		$text_provider = Inkbridge_Gen_Provider_Factory::create_text_provider( $tp_id, $tp_key, $tp_cfg['model'] ?? '' );

		// Image provider.
		$image_handler = null;
		$ip_id  = $image_provider_id ?: $settings->get_active_image_provider();
		$ip_key = $settings->get_provider_api_key( $ip_id );
		if ( $ip_key ) {
			$image_provider = Inkbridge_Gen_Provider_Factory::create_image_provider( $ip_id, $ip_key );
			$image_handler  = new Inkbridge_Gen_Image_Handler( $image_provider, $settings, $logger );
		}

		$generator  = new Inkbridge_Gen_Generator( $text_provider, $settings, $logger );
		$translator = new Inkbridge_Gen_Translator( $text_provider, $settings, $logger );
		$publisher  = new Inkbridge_Gen_Publisher( $settings, $logger );

		return new self( $generator, $translator, $publisher, $image_handler, $settings );
	}
}
