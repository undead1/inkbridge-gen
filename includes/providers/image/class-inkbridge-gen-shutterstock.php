<?php
/**
 * Shutterstock image provider for Inkbridge Generator.
 *
 * @package Inkbridge_Gen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Inkbridge_Gen_Shutterstock
 *
 * Implements the Inkbridge_Gen_Image_Provider interface using the Shutterstock API.
 */
class Inkbridge_Gen_Shutterstock implements Inkbridge_Gen_Image_Provider {

	/**
	 * API search endpoint URL.
	 *
	 * @var string
	 */
	private const SEARCH_URL = 'https://api.shutterstock.com/v2/images/search';

	/**
	 * API licensing endpoint URL.
	 *
	 * @var string
	 */
	private const LICENSE_URL = 'https://api.shutterstock.com/v2/images/licenses';

	/**
	 * API key (OAuth Bearer token) for authentication.
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
		return 'shutterstock';
	}

	/**
	 * Get the provider display name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'Shutterstock';
	}

	/**
	 * Set the API key for authentication.
	 *
	 * @param string $key API key (Bearer token).
	 * @return void
	 */
	public function set_api_key( string $key ): void {
		$this->api_key = $key;
	}

	/**
	 * Map Shutterstock orientation value.
	 *
	 * Shutterstock uses "horizontal" and "vertical" instead of "landscape" and "portrait".
	 *
	 * @param string $orientation Standard orientation value.
	 * @return string Shutterstock-compatible orientation value.
	 */
	private function map_orientation( string $orientation ): string {
		$map = array(
			'landscape' => 'horizontal',
			'portrait'  => 'vertical',
		);

		return isset( $map[ $orientation ] ) ? $map[ $orientation ] : $orientation;
	}

	/**
	 * Search for images on Shutterstock.
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
			throw new RuntimeException( 'Shutterstock API key is not set.' );
		}

		$url = add_query_arg(
			array(
				'query'       => $query,
				'orientation' => $this->map_orientation( $orientation ),
				'per_page'    => $per_page,
			),
			self::SEARCH_URL
		);

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 30,
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->api_key,
					'Accept'        => 'application/json',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			throw new RuntimeException( 'Shutterstock API request failed: ' . $response->get_error_message() );
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $status_code !== 200 ) {
			$error_message = isset( $body['message'] ) ? $body['message'] : 'Unknown error';
			throw new RuntimeException( 'Shutterstock API error (' . $status_code . '): ' . $error_message );
		}

		if ( ! isset( $body['data'] ) || ! is_array( $body['data'] ) ) {
			throw new RuntimeException( 'Shutterstock API returned an unexpected response format.' );
		}

		$images = array();

		foreach ( $body['data'] as $result ) {
			$preview_url = '';
			$thumb_url   = '';

			if ( isset( $result['assets']['preview']['url'] ) ) {
				$preview_url = $result['assets']['preview']['url'];
			} elseif ( isset( $result['assets']['large_thumb']['url'] ) ) {
				$preview_url = $result['assets']['large_thumb']['url'];
			}

			if ( isset( $result['assets']['small_thumb']['url'] ) ) {
				$thumb_url = $result['assets']['small_thumb']['url'];
			} elseif ( isset( $result['assets']['large_thumb']['url'] ) ) {
				$thumb_url = $result['assets']['large_thumb']['url'];
			}

			$width  = 0;
			$height = 0;

			if ( isset( $result['assets']['preview']['width'] ) ) {
				$width = (int) $result['assets']['preview']['width'];
			}

			if ( isset( $result['assets']['preview']['height'] ) ) {
				$height = (int) $result['assets']['preview']['height'];
			}

			$contributor_id = isset( $result['contributor']['id'] ) ? $result['contributor']['id'] : '';

			$images[] = array(
				'id'                   => isset( $result['id'] ) ? (string) $result['id'] : '',
				'url'                  => $preview_url,
				'thumb_url'            => $thumb_url,
				'photographer'         => $contributor_id,
				'photographer_url'     => $contributor_id ? 'https://www.shutterstock.com/g/' . $contributor_id : '',
				'source_url'           => 'https://www.shutterstock.com/image-photo/' . ( isset( $result['id'] ) ? $result['id'] : '' ),
				'description'          => isset( $result['description'] ) ? (string) $result['description'] : '',
				'width'                => $width,
				'height'               => $height,
				'download_trigger_url' => isset( $result['id'] ) ? (string) $result['id'] : '',
			);
		}

		return $images;
	}

	/**
	 * Trigger a download/license for the given image.
	 *
	 * Note: Licensing images on Shutterstock requires an active subscription.
	 * This method sends a licensing request to the Shutterstock API. The actual
	 * download will only succeed if the account has an active subscription with
	 * available downloads.
	 *
	 * @param string $download_url The image ID to license (stored in download_trigger_url).
	 * @return void
	 */
	public function trigger_download( string $download_url ): void {
		if ( empty( $download_url ) || empty( $this->api_key ) ) {
			return;
		}

		// The download_url parameter contains the image ID for Shutterstock.
		// Licensing requires an active Shutterstock subscription.
		wp_remote_post(
			self::LICENSE_URL,
			array(
				'timeout' => 30,
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'images' => array(
							array(
								'image_id' => $download_url,
							),
						),
					)
				),
			)
		);
	}

	/**
	 * Get the attribution HTML for an image.
	 *
	 * @param array $image Image data array.
	 * @return string Attribution HTML.
	 */
	public function get_attribution_html( array $image ): string {
		return 'Image from <a href="https://www.shutterstock.com">Shutterstock</a>';
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
