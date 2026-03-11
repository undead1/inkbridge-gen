<?php
/**
 * OpenAI text provider for Inkbridge Generator.
 *
 * @package Inkbridge_Gen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Inkbridge_Gen_OpenAI
 *
 * Implements the Inkbridge_Gen_Text_Provider interface using the OpenAI Chat Completions API.
 */
class Inkbridge_Gen_OpenAI implements Inkbridge_Gen_Text_Provider {

	/**
	 * API endpoint URL.
	 *
	 * @var string
	 */
	private const API_URL = 'https://api.openai.com/v1/chat/completions';

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
	private string $model = 'gpt-4o-mini';

	/**
	 * Get the provider identifier.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'openai';
	}

	/**
	 * Get the provider display name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'OpenAI';
	}

	/**
	 * Get available models for this provider.
	 *
	 * @return array<string, string> Model ID => Model display name.
	 */
	public function get_available_models(): array {
		return array(
			'gpt-4o-mini'  => 'GPT-4o Mini',
			'gpt-4o'       => 'GPT-4o',
			'gpt-4.1-nano' => 'GPT-4.1 Nano',
			'gpt-4.1-mini' => 'GPT-4.1 Mini',
			'gpt-4.1'      => 'GPT-4.1',
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
	 * Generate text using the OpenAI Chat Completions API.
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
			throw new RuntimeException( 'OpenAI API key is not set.' );
		}

		$response = wp_remote_post(
			self::API_URL,
			array(
				'timeout' => 120,
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $this->api_key,
				),
				'body'    => wp_json_encode(
					array(
						'model'       => $this->model,
						'messages'    => array(
							array(
								'role'    => 'system',
								'content' => $system_prompt,
							),
							array(
								'role'    => 'user',
								'content' => $user_prompt,
							),
						),
						'max_tokens'  => $max_tokens,
						'temperature' => 0.7,
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			throw new RuntimeException( 'OpenAI API request failed: ' . $response->get_error_message() );
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $status_code !== 200 ) {
			$error_message = isset( $body['error']['message'] ) ? $body['error']['message'] : 'Unknown error';
			throw new RuntimeException( 'OpenAI API error (' . $status_code . '): ' . $error_message );
		}

		if ( ! isset( $body['choices'][0]['message']['content'] ) ) {
			throw new RuntimeException( 'OpenAI API returned an unexpected response format.' );
		}

		return array(
			'content'       => $body['choices'][0]['message']['content'],
			'input_tokens'  => isset( $body['usage']['prompt_tokens'] ) ? (int) $body['usage']['prompt_tokens'] : 0,
			'output_tokens' => isset( $body['usage']['completion_tokens'] ) ? (int) $body['usage']['completion_tokens'] : 0,
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
