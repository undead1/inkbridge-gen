<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface Inkbridge_Gen_Text_Provider {

	public function get_id(): string;

	public function get_name(): string;

	public function get_available_models(): array;

	public function set_api_key( string $key ): void;

	public function set_model( string $model ): void;

	/**
	 * @return array{ content: string, input_tokens: int, output_tokens: int, model: string }
	 */
	public function generate( string $system_prompt, string $user_prompt, int $max_tokens = 4096 ): array;

	/**
	 * @return true|string True on success, error message on failure.
	 */
	public function test_connection(): true|string;
}
