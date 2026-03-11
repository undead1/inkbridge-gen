<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Inkbridge_Gen_DB {

	public static function get_logs_table() {
		global $wpdb;
		return $wpdb->prefix . 'inkbridge_gen_logs';
	}

	public static function get_queue_table() {
		global $wpdb;
		return $wpdb->prefix . 'inkbridge_gen_queue';
	}

	/**
	 * Run DB schema upgrades if needed.
	 */
	public static function maybe_upgrade() {
		$installed = get_option( 'inkbridge_gen_db_version', '1.0' );
		if ( version_compare( $installed, '1.1', '>=' ) ) {
			return;
		}
		// Re-run create_tables via the activator (dbDelta handles adding new columns).
		Inkbridge_Gen_Activator::activate();
	}

	// ─── Logs ────────────────────────────────────────────

	public function insert_log( array $data ) {
		global $wpdb;
		$wpdb->insert( self::get_logs_table(), array(
			'type'              => sanitize_text_field( $data['type'] ?? '' ),
			'provider'          => sanitize_text_field( $data['provider'] ?? '' ),
			'model'             => sanitize_text_field( $data['model'] ?? '' ),
			'prompt_tokens'     => absint( $data['prompt_tokens'] ?? 0 ),
			'completion_tokens' => absint( $data['completion_tokens'] ?? 0 ),
			'status'            => sanitize_text_field( $data['status'] ?? 'success' ),
			'error'             => $data['error'] ?? null,
			'topic'             => sanitize_text_field( $data['topic'] ?? '' ),
			'language'          => sanitize_text_field( $data['language'] ?? '' ),
			'duration_ms'       => absint( $data['duration_ms'] ?? 0 ),
			'created_at'        => current_time( 'mysql' ),
		) );
	}

	public function get_logs( array $args = array() ) {
		global $wpdb;
		$table    = self::get_logs_table();
		list( $where, $values ) = $this->build_log_where( $args );
		$per_page = absint( $args['per_page'] ?? 20 );
		$offset   = absint( $args['offset'] ?? 0 );
		$order    = ( $args['order'] ?? 'DESC' ) === 'ASC' ? 'ASC' : 'DESC';

		$values[] = $per_page;
		$values[] = $offset;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} {$where} ORDER BY created_at {$order} LIMIT %d OFFSET %d",
				$values
			)
		);
	}

	public function get_log_count( array $args = array() ) {
		global $wpdb;
		$table = self::get_logs_table();
		list( $where, $values ) = $this->build_log_where( $args );

		if ( ! empty( $values ) ) {
			return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} {$where}", $values ) );
		}

		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
	}

	public function get_log_stats( string $range = '30d' ) {
		global $wpdb;
		$table = self::get_logs_table();
		$date  = $this->range_to_date( $range );

		return $wpdb->get_row( $wpdb->prepare(
			"SELECT
				COUNT(*) as total_calls,
				SUM(prompt_tokens) as total_input_tokens,
				SUM(completion_tokens) as total_output_tokens,
				SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as error_count,
				AVG(duration_ms) as avg_duration_ms
			FROM {$table}
			WHERE created_at >= %s",
			$date
		) );
	}

	public function get_daily_token_usage( int $days = 14 ) {
		global $wpdb;
		$table = self::get_logs_table();
		$date  = gmdate( 'Y-m-d', strtotime( "-{$days} days" ) );

		return $wpdb->get_results( $wpdb->prepare(
			"SELECT
				DATE(created_at) as day,
				SUM(prompt_tokens) as input_tokens,
				SUM(completion_tokens) as output_tokens
			FROM {$table}
			WHERE created_at >= %s AND status = 'success'
			GROUP BY DATE(created_at)
			ORDER BY day ASC",
			$date
		) );
	}

	public function cleanup_old_logs( int $days = 90 ) {
		global $wpdb;
		$table = self::get_logs_table();
		$date  = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE created_at < %s", $date ) );
	}

	private function build_log_where( array $args ): array {
		$conditions = array();
		$values     = array();

		if ( ! empty( $args['type'] ) ) {
			$conditions[] = 'type = %s';
			$values[]     = $args['type'];
		}
		if ( ! empty( $args['provider'] ) ) {
			$conditions[] = 'provider = %s';
			$values[]     = $args['provider'];
		}
		if ( ! empty( $args['status'] ) ) {
			$conditions[] = 'status = %s';
			$values[]     = $args['status'];
		}
		if ( ! empty( $args['range'] ) ) {
			$conditions[] = 'created_at >= %s';
			$values[]     = $this->range_to_date( $args['range'] );
		}

		$where = $conditions ? 'WHERE ' . implode( ' AND ', $conditions ) : '';

		return array( $where, $values );
	}

	private function range_to_date( string $range ) {
		$map = array(
			'today' => 'today',
			'7d'    => '-7 days',
			'30d'   => '-30 days',
			'90d'   => '-90 days',
		);
		$rel = $map[ $range ] ?? '-30 days';
		return gmdate( 'Y-m-d H:i:s', strtotime( $rel ) );
	}

	// ─── Queue ───────────────────────────────────────────

	public function insert_queue_item( array $data ) {
		global $wpdb;
		$row = array(
			'topic'         => sanitize_text_field( $data['topic'] ?? '' ),
			'pillar'        => sanitize_text_field( $data['pillar'] ?? '' ),
			'word_count'    => absint( $data['word_count'] ?? 0 ),
			'languages'     => wp_json_encode( $data['languages'] ?? array() ),
			'extra_context' => $data['extra_context'] ?? null,
			'status'        => 'pending',
			'priority'      => absint( $data['priority'] ?? 10 ),
			'created_at'    => current_time( 'mysql' ),
		);
		if ( ! empty( $data['options'] ) ) {
			$row['options'] = is_string( $data['options'] ) ? $data['options'] : wp_json_encode( $data['options'] );
		}
		$wpdb->insert( self::get_queue_table(), $row );
		return $wpdb->insert_id;
	}

	public function insert_queue_batch( array $items ) {
		$count = 0;
		foreach ( $items as $item ) {
			if ( $this->insert_queue_item( $item ) ) {
				$count++;
			}
		}
		return $count;
	}

	public function get_queue_items( array $args = array() ) {
		global $wpdb;
		$table    = self::get_queue_table();
		$where    = '';
		$per_page = absint( $args['per_page'] ?? 20 );
		$offset   = absint( $args['offset'] ?? 0 );

		if ( ! empty( $args['status'] ) ) {
			$where = $wpdb->prepare( 'WHERE status = %s', $args['status'] );
		}

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} {$where} ORDER BY priority ASC, created_at ASC LIMIT %d OFFSET %d",
				$per_page,
				$offset
			)
		);
	}

	public function get_queue_item( int $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare(
			'SELECT * FROM ' . self::get_queue_table() . ' WHERE id = %d',
			$id
		) );
	}

	public function update_queue_item( int $id, array $data ) {
		global $wpdb;
		return (bool) $wpdb->update( self::get_queue_table(), $data, array( 'id' => $id ) );
	}

	public function get_next_pending() {
		global $wpdb;
		return $wpdb->get_row(
			'SELECT * FROM ' . self::get_queue_table() . " WHERE status = 'pending' ORDER BY priority ASC, created_at ASC LIMIT 1"
		);
	}

	public function get_queue_counts() {
		global $wpdb;
		$table = self::get_queue_table();
		$rows  = $wpdb->get_results( "SELECT status, COUNT(*) as cnt FROM {$table} GROUP BY status" );
		$counts = array( 'pending' => 0, 'processing' => 0, 'completed' => 0, 'failed' => 0 );
		foreach ( $rows as $row ) {
			$counts[ $row->status ] = (int) $row->cnt;
		}
		return $counts;
	}

	public function get_queue_count( array $args = array() ) {
		global $wpdb;
		$table = self::get_queue_table();
		$where = '';
		if ( ! empty( $args['status'] ) ) {
			$where = $wpdb->prepare( 'WHERE status = %s', $args['status'] );
		}
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} {$where}" );
	}

	public function clear_completed() {
		global $wpdb;
		return $wpdb->query( "DELETE FROM " . self::get_queue_table() . " WHERE status = 'completed'" );
	}

	public function clear_failed() {
		global $wpdb;
		return $wpdb->query( "DELETE FROM " . self::get_queue_table() . " WHERE status = 'failed'" );
	}

	public function delete_queue_item( int $id ) {
		global $wpdb;
		return (bool) $wpdb->delete( self::get_queue_table(), array( 'id' => $id ) );
	}
}
