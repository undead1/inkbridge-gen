<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Inkbridge_Gen_Queue_Processor {

	private $db;
	private $settings;

	public function __construct( Inkbridge_Gen_DB $db, Inkbridge_Gen_Settings $settings ) {
		$this->db       = $db;
		$this->settings = $settings;
	}

	/**
	 * Process the next pending queue item.
	 *
	 * @return bool True if an item was processed.
	 */
	public function process_next(): bool {
		$item = $this->db->get_next_pending();
		if ( ! $item ) {
			return false;
		}

		return $this->process_item( $item );
	}

	/**
	 * Process a specific queue item by ID.
	 */
	public function process_item_by_id( int $id ): bool {
		$item = $this->db->get_queue_item( $id );
		if ( ! $item ) {
			return false;
		}

		return $this->process_item( $item );
	}

	private function process_item( object $item ): bool {
		// Mark as processing.
		$this->db->update_queue_item( $item->id, array( 'status' => 'processing' ) );

		try {
			$options   = ! empty( $item->options ) ? json_decode( $item->options, true ) : array();
			$options   = is_array( $options ) ? $options : array();
			$languages = json_decode( $item->languages, true ) ?: array();

			$pipeline = Inkbridge_Gen_Pipeline::from_settings(
				$this->settings,
				$options['text_provider'] ?? '',
				$options['image_provider'] ?? ''
			);

			$result = $pipeline->run( $item->topic, $item->pillar, array(
				'word_count'    => $item->word_count ?: 0,
				'extra_context' => $item->extra_context ?? '',
				'languages'     => $languages,
				'status'        => $options['status'] ?? '',
				'skip_image'    => ! empty( $options['skip_image'] ),
			) );

			if ( $result['success'] ) {
				$this->db->update_queue_item( $item->id, array(
					'status'       => 'completed',
					'result_data'  => wp_json_encode( $result ),
					'processed_at' => current_time( 'mysql' ),
				) );
			} else {
				$errors = implode( '; ', $result['errors'] );
				$this->db->update_queue_item( $item->id, array(
					'status'        => 'failed',
					'error_message' => $errors,
					'result_data'   => wp_json_encode( $result ),
					'processed_at'  => current_time( 'mysql' ),
				) );
			}

			return true;
		} catch ( \Throwable $e ) {
			$this->db->update_queue_item( $item->id, array(
				'status'        => 'failed',
				'error_message' => $e->getMessage(),
				'processed_at'  => current_time( 'mysql' ),
			) );
			return true;
		}
	}

	public function get_pending_count(): int {
		$counts = $this->db->get_queue_counts();
		return $counts['pending'];
	}

	public function is_processing(): bool {
		$counts = $this->db->get_queue_counts();
		return $counts['processing'] > 0;
	}
}
