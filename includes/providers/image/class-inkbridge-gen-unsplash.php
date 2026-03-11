<?php
/**
 * Unsplash image provider for Inkbridge Generator.
 *
 * @package Inkbridge_Gen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Inkbridge_Gen_Unsplash
 *
 * Implements the Inkbridge_Gen_Image_Provider interface using the Unsplash API.
 */
class Inkbridge_Gen_Unsplash implements Inkbridge_Gen_Image_Provider {

	/**
	 * API search endpoint URL.
	 *
	 * @var string
	 */
	private const SEARCH_URL = 'https://api.unsplash.com/search/photos';

	/**
	 * API key (Access Key) for authentication.
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
		return 'unsplash';
	}

	/**
	 * Get the provider display name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'Unsplash';
	}

	/**
	 * Set the API key for authentication.
	 *
	 * @param string $key API key (Unsplash Access Key).
	 * @return void
	 */
	public function set_api_key( string $key ): void {
		$this->api_key = $key;
	}

	/**
	 * Search for images on Unsplash.
	 *
	 * @param string $query       Search query.
	 * @param string $orientation Image orientation (landscape, portrait, squarish).
	 * @param int    $per_page    Number of results per page.
	 * @return array<int, array{ id: string, url: string, thumb_url: string, photographer: string, photographer_url: string, source_url: string, description: string, width: int, height: int, download_trigger_url: string }>
	 *
	 * @throws RuntimeException If the API request fails.
	 */
	public function search( string $query, string $orientation = 'landscape', int $per_page = 5 ): array {
		if ( empty( $this->api_key ) ) {
			throw new RuntimeException( 'Unsplash API key is not set.' );
		}

		$url = add_query_arg(
			array(
				'query'          => $query,
				'orientation'    => $orientation,
				'per_page'       => $per_page,
				'content_filter' => 'high',
			),
			self::SEARCH_URL
		);

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 30,
				'headers' => array(
					'Authorization' => 'Client-ID ' . $this->api_key,
					'Accept'        => 'application/json',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			throw new RuntimeException( 'Unsplash API request failed: ' . $response->get_error_message() );
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $status_code !== 200 ) {
			$error_message = isset( $body['errors'][0] ) ? $body['errors'][0] : 'Unknown error';
			throw new RuntimeException( 'Unsplash API error (' . $status_code . '): ' . $error_message );
		}

		if ( ! isset( $body['results'] ) || ! is_array( $body['results'] ) ) {
			throw new RuntimeException( 'Unsplash API returned an unexpected response format.' );
		}

		$images = array();

		foreach ( $body['results'] as $result ) {
			$images[] = array(
				'id'                   => isset( $result['id'] ) ? (string) $result['id'] : '',
				'url'                  => isset( $result['urls']['regular'] ) ? $result['urls']['regular'] : '',
				'thumb_url'            => isset( $result['urls']['thumb'] ) ? $result['urls']['thumb'] : '',
				'photographer'         => isset( $result['user']['name'] ) ? $result['user']['name'] : '',
				'photographer_url'     => isset( $result['user']['links']['html'] ) ? $result['user']['links']['html'] : '',
				'source_url'           => isset( $result['links']['html'] ) ? $result['links']['html'] : '',
				'description'          => isset( $result['description'] ) ? (string) $result['description'] : ( isset( $result['alt_description'] ) ? (string) $result['alt_description'] : '' ),
				'width'                => isset( $result['width'] ) ? (int) $result['width'] : 0,
				'height'               => isset( $result['height'] ) ? (int) $result['height'] : 0,
				'download_trigger_url' => isset( $result['links']['download_location'] ) ? $result['links']['download_location'] : '',
			);
		}

		return $images;
	}

	/**
	 * Trigger a download event for the given image.
	 *
	 * This is required by the Unsplash API Terms of Service. When an image is
	 * downloaded or used, the download_location endpoint must be pinged to
	 * properly attribute the photographer.
	 *
	 * @param string $download_url The download trigger URL from the image data.
	 * @return void
	 */
	public function trigger_download( string $download_url ): void {
		if ( empty( $download_url ) || empty( $this->api_key ) ) {
			return;
		}

		wp_remote_get(
			$download_url,
			array(
				'timeout' => 30,
				'headers' => array(
					'Authorization' => 'Client-ID ' . $this->api_key,
				),
			)
		);
	}

	/**
	 * Get the attribution HTML for an image.
	 *
	 * Follows Unsplash guidelines for proper attribution with UTM parameters.
	 *
	 * @param array $image Image data array.
	 * @return string Attribution HTML.
	 */
	public function get_attribution_html( array $image ): string {
		$photographer     = isset( $image['photographer'] ) ? esc_html( $image['photographer'] ) : '';
		$photographer_url = isset( $image['photographer_url'] ) ? esc_url( $image['photographer_url'] . '?utm_source=inkbridge-gen&utm_medium=referral' ) : '';

		return sprintf(
			'Photo by <a href="%s">%s</a> on <a href="%s">Unsplash</a>',
			$photographer_url,
			$photographer,
			esc_url( 'https://unsplash.com/?utm_source=inkbridge-gen&utm_medium=referral' )
		);
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
