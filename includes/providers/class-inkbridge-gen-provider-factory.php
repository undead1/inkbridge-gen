<?php
/**
 * Provider factory for Inkbridge Generator.
 *
 * @package Inkbridge_Gen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Inkbridge_Gen_Provider_Factory
 *
 * Factory class for creating text and image provider instances.
 */
class Inkbridge_Gen_Provider_Factory {

	/**
	 * Create a text provider instance.
	 *
	 * @param string $provider_id Provider identifier (openai, claude, gemini).
	 * @param string $api_key     API key for the provider.
	 * @param string $model       Optional model identifier.
	 * @return Inkbridge_Gen_Text_Provider
	 *
	 * @throws RuntimeException If the provider ID is unknown.
	 */
	public static function create_text_provider( string $provider_id, string $api_key, string $model = '' ): Inkbridge_Gen_Text_Provider {
		switch ( $provider_id ) {
			case 'openai':
				$provider = new Inkbridge_Gen_OpenAI();
				break;

			case 'claude':
				$provider = new Inkbridge_Gen_Claude();
				break;

			case 'gemini':
				$provider = new Inkbridge_Gen_Gemini();
				break;

			default:
				throw new RuntimeException( 'Unknown text provider: ' . $provider_id );
		}

		$provider->set_api_key( $api_key );

		if ( ! empty( $model ) ) {
			$provider->set_model( $model );
		}

		return $provider;
	}

	/**
	 * Create an image provider instance.
	 *
	 * @param string $provider_id Provider identifier (unsplash, shutterstock, depositphotos).
	 * @param string $api_key     API key for the provider.
	 * @return Inkbridge_Gen_Image_Provider
	 *
	 * @throws RuntimeException If the provider ID is unknown.
	 */
	public static function create_image_provider( string $provider_id, string $api_key ): Inkbridge_Gen_Image_Provider {
		switch ( $provider_id ) {
			case 'unsplash':
				$provider = new Inkbridge_Gen_Unsplash();
				break;

			case 'shutterstock':
				$provider = new Inkbridge_Gen_Shutterstock();
				break;

			case 'depositphotos':
				$provider = new Inkbridge_Gen_Depositphotos();
				break;

			default:
				throw new RuntimeException( 'Unknown image provider: ' . $provider_id );
		}

		$provider->set_api_key( $api_key );

		return $provider;
	}

	/**
	 * Get all available text provider IDs.
	 *
	 * @return array<int, string>
	 */
	public static function get_text_provider_ids(): array {
		return array( 'openai', 'claude', 'gemini' );
	}

	/**
	 * Get all available image provider IDs.
	 *
	 * @return array<int, string>
	 */
	public static function get_image_provider_ids(): array {
		return array( 'unsplash', 'shutterstock', 'depositphotos' );
	}

	/**
	 * Get the human-readable name for a text provider.
	 *
	 * @param string $id Provider identifier.
	 * @return string Provider display name.
	 */
	public static function get_text_provider_name( string $id ): string {
		$names = array(
			'openai' => 'OpenAI',
			'claude' => 'Claude (Anthropic)',
			'gemini' => 'Google Gemini',
		);

		return isset( $names[ $id ] ) ? $names[ $id ] : $id;
	}

	/**
	 * Get the human-readable name for an image provider.
	 *
	 * @param string $id Provider identifier.
	 * @return string Provider display name.
	 */
	public static function get_image_provider_name( string $id ): string {
		$names = array(
			'unsplash'      => 'Unsplash',
			'shutterstock'  => 'Shutterstock',
			'depositphotos' => 'Depositphotos',
		);

		return isset( $names[ $id ] ) ? $names[ $id ] : $id;
	}
}
