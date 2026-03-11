<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Inkbridge_Gen_Dashboard {

	private $db;
	private $settings;

	public function __construct() {
		$this->db       = new Inkbridge_Gen_DB();
		$this->settings = new Inkbridge_Gen_Settings();
	}

	public function get_stats() {
		$queue_counts = $this->db->get_queue_counts();
		$log_stats    = $this->db->get_log_stats( '30d' );
		$today_stats  = $this->db->get_log_stats( 'today' );

		return array(
			'articles_generated' => $queue_counts['completed'],
			'queue_pending'      => $queue_counts['pending'],
			'tokens_month'       => ( $log_stats->total_input_tokens ?? 0 ) + ( $log_stats->total_output_tokens ?? 0 ),
			'api_calls_today'    => $today_stats->total_calls ?? 0,
			'errors'             => $log_stats->error_count ?? 0,
		);
	}

	public function get_recent_generations( int $limit = 10 ) {
		return $this->db->get_logs( array(
			'type'     => 'generate_article',
			'per_page' => $limit,
		) );
	}

	public function get_provider_status() {
		$text_id  = $this->settings->get_active_text_provider();
		$image_id = $this->settings->get_active_image_provider();

		return array(
			'text'  => array(
				'id'     => $text_id,
				'name'   => Inkbridge_Gen_Provider_Factory::get_text_provider_name( $text_id ),
				'has_key' => $this->settings->has_provider_api_key( $text_id ),
			),
			'image' => array(
				'id'     => $image_id,
				'name'   => Inkbridge_Gen_Provider_Factory::get_image_provider_name( $image_id ),
				'has_key' => $this->settings->has_provider_api_key( $image_id ),
			),
		);
	}
}
