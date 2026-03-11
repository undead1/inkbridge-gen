<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface Inkbridge_Gen_Image_Provider {

	public function get_id(): string;

	public function get_name(): string;

	public function set_api_key( string $key ): void;

	/**
	 * @return array<int, array{ id: string, url: string, thumb_url: string, photographer: string, photographer_url: string, source_url: string, description: string, width: int, height: int, download_trigger_url: string }>
	 */
	public function search( string $query, string $orientation = 'landscape', int $per_page = 5 ): array;

	public function trigger_download( string $download_url ): void;

	public function get_attribution_html( array $image ): string;

	/**
	 * @return true|string
	 */
	public function test_connection(): true|string;
}
