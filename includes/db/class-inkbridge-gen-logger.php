<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Inkbridge_Gen_Logger {

	private $db;

	public function __construct( Inkbridge_Gen_DB $db ) {
		$this->db = $db;
	}

	public function log( array $data ) {
		$this->db->insert_log( $data );
	}

	public function log_generation( string $provider, string $model, int $input_tokens, int $output_tokens, string $topic, string $language, int $duration_ms ) {
		$this->log( array(
			'type'              => 'generate_article',
			'provider'          => $provider,
			'model'             => $model,
			'prompt_tokens'     => $input_tokens,
			'completion_tokens' => $output_tokens,
			'status'            => 'success',
			'topic'             => $topic,
			'language'          => $language,
			'duration_ms'       => $duration_ms,
		) );
	}

	public function log_translation( string $provider, string $model, int $input_tokens, int $output_tokens, string $topic, string $language, int $duration_ms ) {
		$this->log( array(
			'type'              => 'translate',
			'provider'          => $provider,
			'model'             => $model,
			'prompt_tokens'     => $input_tokens,
			'completion_tokens' => $output_tokens,
			'status'            => 'success',
			'topic'             => $topic,
			'language'          => $language,
			'duration_ms'       => $duration_ms,
		) );
	}

	public function log_image( string $provider, string $topic, int $duration_ms, string $status = 'success', string $error = '' ) {
		$this->log( array(
			'type'              => 'image_search',
			'provider'          => $provider,
			'model'             => '',
			'prompt_tokens'     => 0,
			'completion_tokens' => 0,
			'status'            => $status,
			'error'             => $error ?: null,
			'topic'             => $topic,
			'language'          => '',
			'duration_ms'       => $duration_ms,
		) );
	}

	public function log_error( string $type, string $provider, string $error, string $topic = '', string $language = '' ) {
		$this->log( array(
			'type'     => $type,
			'provider' => $provider,
			'status'   => 'error',
			'error'    => $error,
			'topic'    => $topic,
			'language' => $language,
		) );
	}
}
