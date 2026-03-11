<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Inkbridge_Gen_Image_Handler {

	private $image_provider;
	private $settings;
	private $logger;

	public function __construct( Inkbridge_Gen_Image_Provider $provider, Inkbridge_Gen_Settings $settings, Inkbridge_Gen_Logger $logger ) {
		$this->image_provider = $provider;
		$this->settings       = $settings;
		$this->logger         = $logger;
	}

	/**
	 * Search, download, and sideload an image into the WP media library.
	 *
	 * @return int Attachment ID, or 0 on failure.
	 */
	public function fetch_and_attach( string $query, string $title = '', string $alt_text = '' ): int {
		$suffix      = $this->settings->get( 'image_search_suffix', '' );
		$orientation = $this->settings->get( 'image_orientation', 'landscape' );
		$search_query = trim( $query . ' ' . $suffix );

		$start = microtime( true );

		try {
			$results = $this->image_provider->search( $search_query, $orientation, 1 );
		} catch ( \RuntimeException $e ) {
			$duration_ms = (int) ( ( microtime( true ) - $start ) * 1000 );
			$this->logger->log_image( $this->image_provider->get_id(), $query, $duration_ms, 'error', $e->getMessage() );
			return 0;
		}

		$duration_ms = (int) ( ( microtime( true ) - $start ) * 1000 );

		if ( empty( $results ) ) {
			$this->logger->log_image( $this->image_provider->get_id(), $query, $duration_ms, 'success', 'No results found' );
			return 0;
		}

		$image = $results[0];

		// Trigger download tracking/licensing.
		if ( ! empty( $image['download_trigger_url'] ) ) {
			try {
				$this->image_provider->trigger_download( $image['download_trigger_url'] );
			} catch ( \RuntimeException $e ) {
				// Non-fatal.
			}
		}

		// Download image to temp file.
		$tmp_path = $this->download_to_temp( $image['url'] );
		if ( ! $tmp_path ) {
			$this->logger->log_image( $this->image_provider->get_id(), $query, $duration_ms, 'error', 'Download failed' );
			return 0;
		}

		// Get attribution caption.
		$caption = $this->image_provider->get_attribution_html( $image );

		// Sideload into media library.
		$attachment_id = $this->sideload_image( $tmp_path, $title ?: $query, $caption, $alt_text ?: $query );

		$this->logger->log_image( $this->image_provider->get_id(), $query, $duration_ms );

		return $attachment_id;
	}

	private function download_to_temp( string $url ): string|false {
		$response = wp_remote_get( $url, array( 'timeout' => 30 ) );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		if ( empty( $body ) ) {
			return false;
		}

		$content_type = wp_remote_retrieve_header( $response, 'content-type' );
		$ext = '.jpg';
		if ( str_contains( $content_type, 'png' ) ) {
			$ext = '.png';
		} elseif ( str_contains( $content_type, 'webp' ) ) {
			$ext = '.webp';
		}

		$upload_dir = wp_upload_dir();
		$tmp_dir    = $upload_dir['basedir'] . '/inkbridge-gen-tmp';
		if ( ! is_dir( $tmp_dir ) ) {
			wp_mkdir_p( $tmp_dir );
		}

		$filename = 'inkbridge-gen-' . wp_generate_password( 12, false ) . $ext;
		$tmp_path = $tmp_dir . '/' . $filename;

		if ( false === file_put_contents( $tmp_path, $body ) ) {
			return false;
		}

		return $tmp_path;
	}

	private function sideload_image( string $tmp_path, string $title, string $caption, string $alt ): int {
		if ( ! function_exists( 'media_handle_sideload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		$file_array = array(
			'name'     => sanitize_file_name( sanitize_title( $title ) . '-' . wp_generate_password( 6, false ) . '.' . pathinfo( $tmp_path, PATHINFO_EXTENSION ) ),
			'tmp_name' => $tmp_path,
		);

		$attachment_id = media_handle_sideload( $file_array, 0, $title );

		// Clean up temp file if still exists.
		if ( file_exists( $tmp_path ) ) {
			wp_delete_file( $tmp_path );
		}

		if ( is_wp_error( $attachment_id ) ) {
			return 0;
		}

		// Set alt text.
		if ( $alt ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $alt ) );
		}

		// Set caption.
		if ( $caption ) {
			wp_update_post( array(
				'ID'           => $attachment_id,
				'post_excerpt' => $caption,
			) );
		}

		return $attachment_id;
	}
}
