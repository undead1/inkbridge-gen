<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Inkbridge_Gen_Translator {

	private $text_provider;
	private $settings;
	private $logger;
	private $generator;

	public function __construct( Inkbridge_Gen_Text_Provider $provider, Inkbridge_Gen_Settings $settings, Inkbridge_Gen_Logger $logger ) {
		$this->text_provider = $provider;
		$this->settings      = $settings;
		$this->logger        = $logger;
		$this->generator     = new Inkbridge_Gen_Generator( $provider, $settings, $logger );
	}

	public function translate( array $article, string $target_lang ): array {
		$languages = $this->settings->get_languages();
		$lang_name = '';
		foreach ( $languages as $lang ) {
			if ( $lang['code'] === $target_lang ) {
				$lang_name = $lang['name'];
				break;
			}
		}
		if ( ! $lang_name ) {
			throw new \RuntimeException( "Unknown target language: {$target_lang}" );
		}

		$system_prompt = $this->build_translation_system_prompt( $lang_name );
		$user_prompt   = $this->build_translation_user_prompt( $article, $lang_name );

		$config     = $this->settings->get_text_provider_config();
		$max_tokens = $config['max_tokens'] ?? 4096;

		$start = microtime( true );

		try {
			$result      = $this->text_provider->generate( $system_prompt, $user_prompt, $max_tokens );
			$duration_ms = (int) ( ( microtime( true ) - $start ) * 1000 );

			$this->logger->log_translation(
				$this->text_provider->get_id(),
				$result['model'],
				$result['input_tokens'],
				$result['output_tokens'],
				$article['title'] ?? '',
				$target_lang,
				$duration_ms
			);
		} catch ( \RuntimeException $e ) {
			$this->logger->log_error( 'translate', $this->text_provider->get_id(), $e->getMessage(), $article['title'] ?? '', $target_lang );
			throw $e;
		}

		$translated = $this->generator->parse_json_response( $result['content'] );

		// Validate critical fields.
		if ( empty( $translated['title'] ) || empty( $translated['content'] ) ) {
			throw new \RuntimeException( 'Missing critical fields in translation: title or content' );
		}

		// Fill missing optional fields.
		if ( empty( $translated['excerpt'] ) ) {
			$plain = wp_strip_all_tags( $translated['content'] );
			$translated['excerpt'] = wp_trim_words( $plain, 35 );
		}
		if ( empty( $translated['seo_title'] ) ) {
			$translated['seo_title'] = mb_substr( $translated['title'], 0, 60 );
		}
		if ( empty( $translated['seo_description'] ) ) {
			$translated['seo_description'] = mb_substr( $translated['excerpt'], 0, 160 );
		}
		if ( empty( $translated['focus_keyword'] ) ) {
			$words = explode( ' ', $translated['title'] );
			$translated['focus_keyword'] = implode( ' ', array_slice( $words, 0, 4 ) );
		}
		if ( ! isset( $translated['tags'] ) || ! is_array( $translated['tags'] ) ) {
			$translated['tags'] = array();
		}
		if ( empty( $translated['slug'] ) ) {
			$translated['slug'] = sanitize_title( $translated['title'] );
		}

		// Add metadata.
		$translated['pillar']          = $article['pillar'] ?? '';
		$translated['language']        = $target_lang;
		$translated['source_language'] = $article['language'] ?? 'en';
		$translated['word_count']      = str_word_count( wp_strip_all_tags( $translated['content'] ) );

		return $translated;
	}

	public function translate_to_all( array $article, array $target_languages = array() ): array {
		if ( empty( $target_languages ) ) {
			$target_languages = array_map(
				fn( $l ) => $l['code'],
				$this->settings->get_target_languages()
			);
		}

		$source_lang = $article['language'] ?? $this->settings->get_source_language()['code'] ?? 'en';
		$result      = array( $source_lang => $article );

		foreach ( $target_languages as $lang ) {
			try {
				$result[ $lang ] = $this->translate( $article, $lang );
			} catch ( \RuntimeException $e ) {
				$result[ $lang ] = array( 'error' => $e->getMessage() );
			}
		}

		return $result;
	}

	private function build_translation_system_prompt( string $lang_name ): string {
		$template = $this->settings->get_prompt( 'translate_system' );
		return str_replace( '{{lang_name}}', $lang_name, $template );
	}

	private function build_translation_user_prompt( array $article, string $lang_name ): string {
		$template = $this->settings->get_prompt( 'translate_user' );

		$replacements = array(
			'{{lang_name}}'                => $lang_name,
			'{{article_title}}'            => $article['title'] ?? '',
			'{{article_content}}'          => $article['content'] ?? '',
			'{{article_excerpt}}'          => $article['excerpt'] ?? '',
			'{{article_seo_title}}'        => $article['seo_title'] ?? '',
			'{{article_seo_description}}'  => $article['seo_description'] ?? '',
			'{{article_focus_keyword}}'    => $article['focus_keyword'] ?? '',
			'{{article_tags}}'             => wp_json_encode( $article['tags'] ?? array() ),
		);

		return str_replace( array_keys( $replacements ), array_values( $replacements ), $template );
	}
}
