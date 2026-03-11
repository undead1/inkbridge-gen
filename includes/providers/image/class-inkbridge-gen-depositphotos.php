<?php
/**
 * Depositphotos image provider for Inkbridge Generator.
 *
 * @package Inkbridge_Gen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Inkbridge_Gen_Depositphotos
 *
 * Implements the Inkbridge_Gen_Image_Provider interface using the Depositphotos API.
 */
class Inkbridge_Gen_Depositphotos implements Inkbridge_Gen_Image_Provider {

	/**
	 * API endpoint URL.
	 *
	 * @var string
	 */
	private const API_URL = 'https://api.depositphotos.com';

	/**
	 * API key for authentication.
	 *
	 * @var string
	 */
	private string $api_key = '';

	/**
	 * Get the provider identifier.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'depositphotos';
	}

	/**
	 * Get the provider display name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'Depositphotos';
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
	 * Map orientation to Depositphotos format.
	 *
	 * @param string $orientation Standard orientation value.
	 * @return string Depositphotos-compatible orientation value.
	 */
	private function map_orientation( string $orientation ): string {
		$map = array(
			'landscape' => 'horizontal',
			'portrait'  => 'vertical',
		);

		return isset( $map[ $orientation ] ) ? $map[ $orientation ] : $orientation;
	}

	/**
	 * Search for images on Depositphotos.
	 *
	 * @param string $query       Search query.
	 * @param string $orientation Image orientation (landscape, portrait).
	 * @param int    $per_page    Number of results per page.
	 * @return array<int, array{ id: string, url: string, thumb_url: string, photographer: string, photographer_url: string, source_url: string, description: string, width: int, height: int, download_trigger_url: string }>
	 *
	 * @throws RuntimeException If the API request fails.
	 */
	public function search( string $query, string $orientation = 'landscape', int $per_page = 5 ): array {
		if ( empty( $this->api_key ) ) {
			throw new RuntimeException( 'Depositphotos API key is not set.' );
		}

		$url = add_query_arg(
			array(
				'dp_apikey'             => $this->api_key,
				'dp_command'            => 'search',
				'dp_search_query'       => $query,
				'dp_search_limit'       => $per_page,
				'dp_search_orientation' => $this->map_orientation( $orientation ),
			),
			self::API_URL
		);

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 30,
				'headers' => array(
					'Accept' => 'application/json',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			throw new RuntimeException( 'Depositphotos API request failed: ' . $response->get_error_message() );
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $status_code !== 200 ) {
			$error_message = isset( $body['error']['errormsg'] ) ? $body['error']['errormsg'] : 'Unknown error';
			throw new RuntimeException( 'Depositphotos API error (' . $status_code . '): ' . $error_message );
		}

		if ( isset( $body['error'] ) ) {
			$error_message = isset( $body['error']['errormsg'] ) ? $body['error']['errormsg'] : 'Unknown error';
			throw new RuntimeException( 'Depositphotos API error: ' . $error_message );
		}

		$results = array();

		if ( isset( $body['result'] ) && is_array( $body['result'] ) ) {
			$results = $body['result'];
		}

		$images = array();

		foreach ( $results as $result ) {
			$image_id = isset( $result['id'] ) ? (string) $result['id'] : '';

			$url       = '';
			$thumb_url = '';

			if ( isset( $result['url_big'] ) ) {
				$url = $result['url_big'];
			} elseif ( isset( $result['url'] ) ) {
				$url = $result['url'];
			}

			if ( isset( $result['url_small'] ) ) {
				$thumb_url = $result['url_small'];
			} elseif ( isset( $result['thumbnail'] ) ) {
				$thumb_url = $result['thumbnail'];
			}

			$seller_id   = isset( $result['userid'] ) ? (string) $result['userid'] : '';
			$seller_name = isset( $result['username'] ) ? (string) $result['username'] : $seller_id;

			$images[] = array(
				'id'                   => $image_id,
				'url'                  => $url,
				'thumb_url'            => $thumb_url,
				'photographer'         => $seller_name,
				'photographer_url'     => $seller_name ? 'https://depositphotos.com/portfolio-' . rawurlencode( $seller_name ) . '.html' : '',
				'source_url'           => $image_id ? 'https://depositphotos.com/' . $image_id . '.html' : '',
				'description'          => isset( $result['title'] ) ? (string) $result['title'] : '',
				'width'                => isset( $result['width'] ) ? (int) $result['width'] : 0,
				'height'               => isset( $result['height'] ) ? (int) $result['height'] : 0,
				'download_trigger_url' => $image_id,
			);
		}

		return $images;
	}

	/**
	 * Trigger a download for the given image.
	 *
	 * Note: Downloading licensed images on Depositphotos requires an active
	 * subscription or credits. This is a placeholder that logs the intent.
	 *
	 * @param string $download_url The image ID (stored in download_trigger_url).
	 * @return void
	 */
	public function trigger_download( string $download_url ): void {
		if ( empty( $download_url ) || empty( $this->api_key ) ) {
			return;
		}

		// Depositphotos download/licensing requires an active subscription or credits.
		// The download_url parameter contains the image ID.
		// A full implementation would call the media/getMediaData endpoint.
	}

	/**
	 * Get the attribution HTML for an image.
	 *
	 * @param array $image Image data array.
	 * @return string Attribution HTML.
	 */
	public function get_attribution_html( array $image ): string {
		return 'Image from <a href="https://depositphotos.com">Depositphotos</a>';
	}

	/**
	 * Test the API connection with a minimal search request.
	 *
	 * @return true|string True on success, error message on failure.
	 */
	public function test_connection(): true|string {
		try {
			$this->search( 'test', 'landscape', 1 );
			return true;
		} catch ( RuntimeException $e ) {
			return $e->getMessage();
		}
	}
}
