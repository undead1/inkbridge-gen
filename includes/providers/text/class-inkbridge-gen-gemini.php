<?php
/**
 * Google Gemini text provider for Inkbridge Generator.
 *
 * @package Inkbridge_Gen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Inkbridge_Gen_Gemini
 *
 * Implements the Inkbridge_Gen_Text_Provider interface using the Google Gemini API.
 */
class Inkbridge_Gen_Gemini implements Inkbridge_Gen_Text_Provider {

	/**
	 * Base API URL (model and key are appended dynamically).
	 *
	 * @var string
	 */
	private const API_BASE_URL = 'https://generativelanguage.googleapis.com/v1beta/models/';

	/**
	 * API key for authentication.
	 *
	 * @var string
	 */
	private string $api_key = '';

	/**
	 * Model identifier.
	 *
	 * @var string
	 */
	private string $model = 'gemini-2.0-flash';

	/**
	 * Get the provider identifier.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'gemini';
	}

	/**
	 * Get the provider display name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'Google Gemini';
	}

	/**
	 * Get available models for this provider.
	 *
	 * @return array<string, string> Model ID => Model display name.
	 */
	public function get_available_models(): array {
		return array(
			'gemini-2.0-flash' => 'Gemini 2.0 Flash',
			'gemini-2.5-pro'   => 'Gemini 2.5 Pro',
			'gemini-2.5-flash' => 'Gemini 2.5 Flash',
		);
	}

	/**
	 * Set the API key for authentication.
	 *
	 * @param string $key API key.
	 * @return void
	 */
	public function set_api_key( string $key ): void {
		$this->api_key = $key;
	}

	/**
	 * Set the model to use for generation.
	 *
	 * @param string $model Model identifier.
	 * @return void
	 */
	public function set_model( string $model ): void {
		$this->model = $model;
	}

	/**
	 * Build the full API URL for the current model and API key.
	 *
	 * @return string
	 */
	private function get_api_url(): string {
		return self::API_BASE_URL . rawurlencode( $this->model ) . ':generateContent?key=' . rawurlencode( $this->api_key );
	}

	/**
	 * Generate text using the Google Gemini API.
	 *
	 * @param string $system_prompt System prompt providing context and instructions.
	 * @param string $user_prompt   User prompt with the specific request.
	 * @param int    $max_tokens    Maximum number of tokens in the response.
	 * @return array{ content: string, input_tokens: int, output_tokens: int, model: string }
	 *
	 * @throws RuntimeException If the API request fails.
	 */
	public function generate( string $system_prompt, string $user_prompt, int $max_tokens = 4096 ): array {
		if ( empty( $this->api_key ) ) {
			throw new RuntimeException( 'Gemini API key is not set.' );
		}

		$response = wp_remote_post(
			$this->get_api_url(),
			array(
				'timeout' => 120,
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'system_instruction' => array(
							'parts' => array(
								array(
									'text' => $system_prompt,
								),
							),
						),
						'contents'           => array(
							array(
								'parts' => array(
									array(
										'text' => $user_prompt,
									),
								),
							),
						),
						'generationConfig'   => array(
							'maxOutputTokens' => $max_tokens,
							'temperature'     => 0.7,
						),
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			throw new RuntimeException( 'Gemini API request failed: ' . $response->get_error_message() );
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $status_code !== 200 ) {
			$error_message = isset( $body['error']['message'] ) ? $body['error']['message'] : 'Unknown error';
			throw new RuntimeException( 'Gemini API error (' . $status_code . '): ' . $error_message );
		}

		if ( ! isset( $body['candidates'][0]['content']['parts'][0]['text'] ) ) {
			throw new RuntimeException( 'Gemini API returned an unexpected response format.' );
		}

		return array(
			'content'       => $body['candidates'][0]['content']['parts'][0]['text'],
			'input_tokens'  => isset( $body['usageMetadata']['promptTokenCount'] ) ? (int) $body['usageMetadata']['promptTokenCount'] : 0,
			'output_tokens' => isset( $body['usageMetadata']['candidatesTokenCount'] ) ? (int) $body['usageMetadata']['candidatesTokenCount'] : 0,
			'model'         => $this->model,
		);
	}

	/**
	 * Test the API connection with a minimal request.
	 *
	 * @return true|string True on success, error message on failure.
	 */
	public function test_connection(): true|string {
		try {
			$this->generate( 'You are a helpful assistant.', 'Say hi', 10 );
			return true;
		} catch ( RuntimeException $e ) {
			return $e->getMessage();
		}
	}
}
