<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Inkbridge_Gen_Scheduler {

	public function register_cron_schedules( $schedules ) {
		$schedules['inkbridge_gen_every_15_min'] = array(
			'interval' => 900,
			'display'  => __( 'Every 15 Minutes (Inkbridge Generator)', 'inkbridge-gen' ),
		);
		$schedules['inkbridge_gen_weekly'] = array(
			'interval' => 604800,
			'display'  => __( 'Once Weekly (Inkbridge Generator)', 'inkbridge-gen' ),
		);
		return $schedules;
	}

	public function schedule_events() {
		$settings = new Inkbridge_Gen_Settings();

		$frequency_map = array(
			'every_15_min' => 'inkbridge_gen_every_15_min',
			'hourly'       => 'hourly',
			'twicedaily'   => 'twicedaily',
			'daily'        => 'daily',
			'weekly'       => 'inkbridge_gen_weekly',
		);

		// Queue processing cron.
		if ( $settings->get( 'cron_enabled' ) ) {
			$frequency = $settings->get( 'cron_frequency', 'daily' );
			$schedule  = $frequency_map[ $frequency ] ?? 'daily';

			if ( ! wp_next_scheduled( 'inkbridge_gen_process_queue' ) ) {
				wp_schedule_event( time(), $schedule, 'inkbridge_gen_process_queue' );
			}
		} else {
			wp_clear_scheduled_hook( 'inkbridge_gen_process_queue' );
		}

		// Auto-generate cron.
		if ( $settings->get( 'autogen_enabled' ) && ! empty( $settings->get( 'autogen_pillars', array() ) ) ) {
			$ag_frequency = $settings->get( 'autogen_frequency', 'daily' );
			$ag_schedule  = $frequency_map[ $ag_frequency ] ?? 'daily';

			if ( ! wp_next_scheduled( 'inkbridge_gen_auto_generate' ) ) {
				$start_time = $this->get_next_start_timestamp( $settings->get( 'autogen_time', '09:00' ) );
				wp_schedule_event( $start_time, $ag_schedule, 'inkbridge_gen_auto_generate' );
			}
		} else {
			wp_clear_scheduled_hook( 'inkbridge_gen_auto_generate' );
		}

		// Log cleanup.
		if ( ! wp_next_scheduled( 'inkbridge_gen_cleanup_logs' ) ) {
			wp_schedule_event( time(), 'daily', 'inkbridge_gen_cleanup_logs' );
		}
	}

	public function unschedule_events() {
		wp_clear_scheduled_hook( 'inkbridge_gen_process_queue' );
		wp_clear_scheduled_hook( 'inkbridge_gen_auto_generate' );
		wp_clear_scheduled_hook( 'inkbridge_gen_cleanup_logs' );
	}

	public function handle_queue_processing() {
		$settings = new Inkbridge_Gen_Settings();
		if ( ! $settings->get( 'cron_enabled' ) ) {
			return;
		}

		// Concurrency guard.
		$lock = get_transient( 'inkbridge_gen_queue_lock' );
		if ( $lock ) {
			return;
		}
		set_transient( 'inkbridge_gen_queue_lock', true, 300 );

		try {
			$db            = new Inkbridge_Gen_DB();
			$max_per_run   = (int) $settings->get( 'cron_max_per_run', 1 );
			$processor     = new Inkbridge_Gen_Queue_Processor( $db, $settings );

			for ( $i = 0; $i < $max_per_run; $i++ ) {
				if ( ! $processor->process_next() ) {
					break;
				}
			}
		} finally {
			delete_transient( 'inkbridge_gen_queue_lock' );
		}
	}

	public function handle_auto_generate() {
		$settings = new Inkbridge_Gen_Settings();
		if ( ! $settings->get( 'autogen_enabled' ) ) {
			return;
		}

		$pillars = $settings->get( 'autogen_pillars', array() );
		if ( empty( $pillars ) ) {
			return;
		}

		// Concurrency guard.
		$lock = get_transient( 'inkbridge_gen_autogen_lock' );
		if ( $lock ) {
			return;
		}
		set_transient( 'inkbridge_gen_autogen_lock', true, 300 );

		try {
			$count       = (int) $settings->get( 'autogen_count', 1 );
			$word_count  = (int) $settings->get( 'autogen_word_count', 1500 );
			$post_status = $settings->get( 'autogen_post_status', 'draft' );
			$languages   = array_map( fn( $l ) => $l['code'], $settings->get_languages() );

			// Pillar rotation index.
			$pillar_index = (int) get_option( 'inkbridge_gen_autogen_pillar_index', 0 );

			// Create text provider for topic suggestion.
			$tp_id  = $settings->get_active_text_provider();
			$tp_key = $settings->get_provider_api_key( $tp_id );
			$tp_cfg = $settings->get_text_provider_config( $tp_id );
			$text_provider = Inkbridge_Gen_Provider_Factory::create_text_provider( $tp_id, $tp_key, $tp_cfg['model'] ?? '' );

			$db     = new Inkbridge_Gen_DB();
			$logger = new Inkbridge_Gen_Logger( $db );

			for ( $i = 0; $i < $count; $i++ ) {
				$pillar_key  = $pillars[ $pillar_index % count( $pillars ) ];
				$pillar_data = $settings->get_pillar( $pillar_key );
				$pillar_index++;

				if ( ! $pillar_data ) {
					continue;
				}

				// Suggest a topic using the AI.
				try {
					$topic = $this->suggest_topic( $text_provider, $pillar_data );
				} catch ( \Throwable $e ) {
					$logger->log_error( 'auto_generate', $tp_id, 'Topic suggestion failed: ' . $e->getMessage() );
					continue;
				}

				// Insert queue item.
				$db->insert_queue_item( array(
					'topic'         => $topic,
					'pillar'        => $pillar_key,
					'word_count'    => $word_count,
					'languages'     => $languages,
					'priority'      => 5,
					'options'       => array(
						'status' => $post_status,
					),
				) );
			}

			update_option( 'inkbridge_gen_autogen_pillar_index', $pillar_index );
		} finally {
			delete_transient( 'inkbridge_gen_autogen_lock' );
		}
	}

	private function suggest_topic( Inkbridge_Gen_Text_Provider $provider, array $pillar_data ): string {
		$system_prompt = 'You are a content strategist. Suggest a single, specific article topic. Reply with ONLY the topic text, nothing else — no quotes, no numbering, no explanation.';

		$user_prompt = 'Suggest one specific, engaging article topic';
		if ( ! empty( $pillar_data['label'] ) ) {
			$user_prompt .= ' for the content pillar "' . $pillar_data['label'] . '"';
		}
		if ( ! empty( $pillar_data['context'] ) ) {
			$user_prompt .= '. Pillar context: ' . wp_strip_all_tags( $pillar_data['context'] );
		}
		$user_prompt .= '. The topic should be suitable for a blog post of around 1500 words.';

		$result = $provider->generate( $system_prompt, $user_prompt, 100 );
		return trim( $result['content'], " \t\n\r\0\x0B\"'" );
	}

	/**
	 * Get the next UTC timestamp for a given local time (HH:MM).
	 * If the time has already passed today, returns tomorrow's timestamp.
	 */
	private function get_next_start_timestamp( string $time_str ): int {
		$tz    = wp_timezone();
		$parts = explode( ':', $time_str );
		$hour  = (int) ( $parts[0] ?? 9 );
		$min   = (int) ( $parts[1] ?? 0 );

		$now   = new \DateTime( 'now', $tz );
		$start = new \DateTime( 'today', $tz );
		$start->setTime( $hour, $min, 0 );

		// If the time has already passed today, schedule for tomorrow.
		if ( $start <= $now ) {
			$start->modify( '+1 day' );
		}

		return $start->getTimestamp();
	}

	public function handle_log_cleanup() {
		$db = new Inkbridge_Gen_DB();
		$db->cleanup_old_logs( 90 );
	}

	public function get_next_run() {
		$timestamp = wp_next_scheduled( 'inkbridge_gen_process_queue' );
		if ( ! $timestamp ) {
			return __( 'Not scheduled', 'inkbridge-gen' );
		}
		return wp_date( 'Y-m-d H:i:s', $timestamp );
	}
}
