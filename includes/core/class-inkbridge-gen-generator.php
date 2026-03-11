<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Inkbridge_Gen_Generator {

	private $text_provider;
	private $settings;
	private $logger;

	public function __construct( Inkbridge_Gen_Text_Provider $provider, Inkbridge_Gen_Settings $settings, Inkbridge_Gen_Logger $logger ) {
		$this->text_provider = $provider;
		$this->settings      = $settings;
		$this->logger        = $logger;
	}

	public function generate( string $topic, string $pillar, int $word_count = 0, string $extra_context = '' ): array {
		if ( ! $word_count ) {
			$word_count = $this->settings->get( 'default_word_count', 1500 );
		}

		$system_prompt = $this->build_system_prompt( $pillar );
		$user_prompt   = $this->build_user_prompt( $topic, $pillar, $word_count, $extra_context );

		$config     = $this->settings->get_text_provider_config();
		$max_tokens = $config['max_tokens'] ?? 4096;

		$start = microtime( true );

		try {
			$result      = $this->text_provider->generate( $system_prompt, $user_prompt, $max_tokens );
			$duration_ms = (int) ( ( microtime( true ) - $start ) * 1000 );

			$this->logger->log_generation(
				$this->text_provider->get_id(),
				$result['model'],
				$result['input_tokens'],
				$result['output_tokens'],
				$topic,
				$this->settings->get_source_language()['code'] ?? 'en',
				$duration_ms
			);
		} catch ( \RuntimeException $e ) {
			$duration_ms = (int) ( ( microtime( true ) - $start ) * 1000 );
			$this->logger->log_error( 'generate_article', $this->text_provider->get_id(), $e->getMessage(), $topic );
			throw $e;
		}

		$article = $this->parse_json_response( $result['content'] );

		// Validate required fields.
		$required = array( 'title', 'content', 'excerpt', 'seo_title', 'seo_description', 'focus_keyword' );
		foreach ( $required as $key ) {
			if ( empty( $article[ $key ] ) ) {
				throw new \RuntimeException( "Missing required field in generated article: {$key}" );
			}
		}

		// Ensure optional fields.
		if ( ! isset( $article['tags'] ) || ! is_array( $article['tags'] ) ) {
			$article['tags'] = array();
		}
		if ( empty( $article['slug'] ) ) {
			$article['slug'] = sanitize_title( $article['title'] );
		}

		// Add metadata.
		$article['pillar']     = $pillar;
		$article['language']   = $this->settings->get_source_language()['code'] ?? 'en';
		$article['word_count'] = str_word_count( wp_strip_all_tags( $article['content'] ) );

		return $article;
	}

	private function build_system_prompt( string $pillar ): string {
		$template = $this->settings->get_prompt( 'generate_system' );
		$pillar_data = $this->settings->get_pillar( $pillar );
		$pillar_context = $pillar_data['context'] ?? '';

		return str_replace( '{{pillar_context}}', $pillar_context, $template );
	}

	private function build_user_prompt( string $topic, string $pillar, int $word_count, string $extra_context ): string {
		$template = $this->settings->get_prompt( 'generate_user' );

		$extra = $extra_context ? "Additional context: {$extra_context}" : '';

		$replacements = array(
			'{{topic}}'         => $topic,
			'{{word_count}}'    => (string) $word_count,
			'{{pillar}}'        => $pillar,
			'{{extra_context}}' => $extra,
		);

		return str_replace( array_keys( $replacements ), array_values( $replacements ), $template );
	}

	/**
	 * Parse JSON from LLM response with code fence stripping and repair.
	 */
	public function parse_json_response( string $raw_text ): array {
		$raw_text = trim( $raw_text );

		// Strip markdown code fences.
		if ( str_starts_with( $raw_text, '```' ) ) {
			$raw_text = preg_replace( '/^```[a-z]*\n?/i', '', $raw_text );
			$raw_text = preg_replace( '/```\s*$/', '', $raw_text );
			$raw_text = trim( $raw_text );
		}

		// Attempt direct parse.
		$article = json_decode( $raw_text, true );
		if ( json_last_error() === JSON_ERROR_NONE && is_array( $article ) ) {
			return $article;
		}

		// Remove control characters (keep \n, \r, \t).
		$repaired = preg_replace( '/[\x00-\x08\x0b\x0c\x0e-\x1f]/', '', $raw_text );
		$article  = json_decode( $repaired, true );
		if ( json_last_error() === JSON_ERROR_NONE && is_array( $article ) ) {
			return $article;
		}

		// Truncation recovery: try closing truncated JSON.
		if ( strpos( $repaired, '"content"' ) !== false ) {
			$len = strlen( $repaired );
			for ( $pos = $len - 1; $pos > max( 0, $len - 200 ); $pos-- ) {
				$attempt = substr( $repaired, 0, $pos );
				foreach ( array( '"}', '"]}', '"\n}' ) as $suffix ) {
					$try = json_decode( $attempt . $suffix, true );
					if ( json_last_error() === JSON_ERROR_NONE && is_array( $try ) ) {
						return $try;
					}
				}
			}
		}

		throw new \RuntimeException( 'Failed to parse JSON from AI response: ' . json_last_error_msg() );
	}
}
