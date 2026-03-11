<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Inkbridge_Gen_Admin_Ajax {

	public function __construct() {
		$actions = array(
			'inkbridge_gen_generate_step',
			'inkbridge_gen_generate_queued',
			'inkbridge_gen_queue_status',
			'inkbridge_gen_process_background',
			'inkbridge_gen_import_queue',
			'inkbridge_gen_process_queue_item',
			'inkbridge_gen_delete_queue_item',
			'inkbridge_gen_retry_queue_item',
			'inkbridge_gen_clear_queue',
			'inkbridge_gen_test_provider',
			'inkbridge_gen_suggest_topic',
			'inkbridge_gen_save_settings',
		);

		foreach ( $actions as $action ) {
			add_action( 'wp_ajax_' . $action, array( $this, str_replace( 'inkbridge_gen_', '', $action ) ) );
		}
	}

	// ─── Generation (step-by-step) ───────────────────────

	public function generate_step() {
		$this->verify_request();

		$step = sanitize_text_field( $_POST['step'] ?? '' );
		$data = array();

		// Parse incoming data.
		if ( ! empty( $_POST['data'] ) ) {
			$data = json_decode( wp_unslash( $_POST['data'] ), true ) ?: array();
		}

		// Allow overriding provider.
		$text_provider_id  = sanitize_text_field( $_POST['text_provider'] ?? '' );
		$image_provider_id = sanitize_text_field( $_POST['image_provider'] ?? '' );

		try {
			$pipeline = Inkbridge_Gen_Pipeline::from_settings( null, $text_provider_id, $image_provider_id );
			$result   = $pipeline->run_step( $step, $data );
			wp_send_json_success( $result );
		} catch ( \Throwable $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	// ─── Queue-based generation ─────────────────────────

	public function generate_queued() {
		$this->verify_request();

		$topic = sanitize_text_field( $_POST['topic'] ?? '' );
		$pillar = sanitize_text_field( $_POST['pillar'] ?? '' );

		if ( ! $topic || ! $pillar ) {
			wp_send_json_error( array( 'message' => __( 'Topic and pillar are required.', 'inkbridge-gen' ) ) );
		}

		$settings      = new Inkbridge_Gen_Settings();
		$default_langs = array_map( fn( $l ) => $l['code'], $settings->get_languages() );

		$languages = ! empty( $_POST['languages'] ) ? array_map( 'sanitize_text_field', (array) $_POST['languages'] ) : $default_langs;

		$options = array(
			'status'         => sanitize_text_field( $_POST['status'] ?? 'draft' ),
			'skip_image'     => ! empty( $_POST['skip_image'] ),
			'text_provider'  => sanitize_text_field( $_POST['text_provider'] ?? '' ),
			'image_provider' => sanitize_text_field( $_POST['image_provider'] ?? '' ),
		);

		$db = new Inkbridge_Gen_DB();
		$item_id = $db->insert_queue_item( array(
			'topic'         => $topic,
			'pillar'        => $pillar,
			'word_count'    => absint( $_POST['word_count'] ?? 0 ),
			'languages'     => $languages,
			'extra_context' => sanitize_textarea_field( $_POST['extra_context'] ?? '' ),
			'priority'      => 1, // High priority for immediate generation.
			'options'       => $options,
		) );

		if ( ! $item_id ) {
			wp_send_json_error( array( 'message' => __( 'Failed to add item to queue.', 'inkbridge-gen' ) ) );
		}

		// Fire non-blocking loopback to process this item in the background.
		$this->spawn_background_processing( $item_id );

		wp_send_json_success( array(
			'item_id' => $item_id,
			'message' => __( 'Article queued for generation.', 'inkbridge-gen' ),
		) );
	}

	public function queue_status() {
		$this->verify_request();

		$item_id = absint( $_POST['item_id'] ?? 0 );
		if ( ! $item_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid item ID.', 'inkbridge-gen' ) ) );
		}

		$db   = new Inkbridge_Gen_DB();
		$item = $db->get_queue_item( $item_id );

		if ( ! $item ) {
			wp_send_json_error( array( 'message' => __( 'Queue item not found.', 'inkbridge-gen' ) ) );
		}

		$data = array(
			'status' => $item->status,
		);

		if ( 'completed' === $item->status && $item->result_data ) {
			$data['result'] = json_decode( $item->result_data, true );
		}
		if ( 'failed' === $item->status ) {
			$data['error'] = $item->error_message;
		}

		wp_send_json_success( $data );
	}

	public function process_background() {
		$this->verify_request();

		$item_id = absint( $_POST['item_id'] ?? 0 );
		if ( ! $item_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid item ID.', 'inkbridge-gen' ) ) );
		}

		$db        = new Inkbridge_Gen_DB();
		$settings  = new Inkbridge_Gen_Settings();
		$processor = new Inkbridge_Gen_Queue_Processor( $db, $settings );

		try {
			$processor->process_item_by_id( $item_id );
			wp_send_json_success( array( 'message' => __( 'Item processed.', 'inkbridge-gen' ) ) );
		} catch ( \Throwable $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	private function spawn_background_processing( int $item_id ) {
		$url  = admin_url( 'admin-ajax.php' );
		$args = array(
			'timeout'   => 0.01,
			'blocking'  => false,
			'sslverify' => false,
			'body'      => array(
				'action'  => 'inkbridge_gen_process_background',
				'nonce'   => wp_create_nonce( 'inkbridge_gen_admin' ),
				'item_id' => $item_id,
			),
			'cookies'   => $_COOKIE,
		);
		wp_remote_post( $url, $args );
	}

	// ─── Queue ───────────────────────────────────────────

	public function import_queue() {
		$this->verify_request();

		$json = wp_unslash( $_POST['topics'] ?? '' );
		$items = json_decode( $json, true );

		if ( ! is_array( $items ) || empty( $items ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid JSON or empty array.', 'inkbridge-gen' ) ) );
		}

		$db    = new Inkbridge_Gen_DB();
		$settings = new Inkbridge_Gen_Settings();
		$default_langs = array_map( fn( $l ) => $l['code'], $settings->get_languages() );

		$valid_items = array();
		foreach ( $items as $item ) {
			if ( empty( $item['topic'] ) || empty( $item['pillar'] ) ) {
				continue;
			}
			$valid_items[] = array(
				'topic'         => sanitize_text_field( $item['topic'] ),
				'pillar'        => sanitize_text_field( $item['pillar'] ),
				'word_count'    => absint( $item['word_count'] ?? 0 ),
				'languages'     => $item['languages'] ?? $default_langs,
				'extra_context' => sanitize_textarea_field( $item['extra_context'] ?? '' ),
				'priority'      => absint( $item['priority'] ?? 10 ),
			);
		}

		$count = $db->insert_queue_batch( $valid_items );
		wp_send_json_success( array(
			'count'   => $count,
			'message' => sprintf( __( '%d topics imported to queue.', 'inkbridge-gen' ), $count ),
		) );
	}

	public function process_queue_item() {
		$this->verify_request();

		$db        = new Inkbridge_Gen_DB();
		$settings  = new Inkbridge_Gen_Settings();
		$processor = new Inkbridge_Gen_Queue_Processor( $db, $settings );

		$item_id = absint( $_POST['item_id'] ?? 0 );

		try {
			if ( $item_id ) {
				$processed = $processor->process_item_by_id( $item_id );
			} else {
				$processed = $processor->process_next();
			}

			if ( $processed ) {
				wp_send_json_success( array( 'message' => __( 'Queue item processed.', 'inkbridge-gen' ) ) );
			} else {
				wp_send_json_success( array( 'message' => __( 'No pending items in queue.', 'inkbridge-gen' ) ) );
			}
		} catch ( \Throwable $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	public function delete_queue_item() {
		$this->verify_request();

		$id = absint( $_POST['item_id'] ?? 0 );
		if ( ! $id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid item ID.', 'inkbridge-gen' ) ) );
		}

		$db = new Inkbridge_Gen_DB();
		$db->delete_queue_item( $id );
		wp_send_json_success( array( 'message' => __( 'Item deleted.', 'inkbridge-gen' ) ) );
	}

	public function retry_queue_item() {
		$this->verify_request();

		$id = absint( $_POST['item_id'] ?? 0 );
		if ( ! $id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid item ID.', 'inkbridge-gen' ) ) );
		}

		$db = new Inkbridge_Gen_DB();
		$db->update_queue_item( $id, array(
			'status'        => 'pending',
			'error_message' => null,
			'result_data'   => null,
			'processed_at'  => null,
		) );
		wp_send_json_success( array( 'message' => __( 'Item reset to pending.', 'inkbridge-gen' ) ) );
	}

	public function clear_queue() {
		$this->verify_request();

		$type = sanitize_text_field( $_POST['type'] ?? 'completed' );
		$db   = new Inkbridge_Gen_DB();

		if ( 'completed' === $type ) {
			$count = $db->clear_completed();
		} elseif ( 'failed' === $type ) {
			$count = $db->clear_failed();
		} else {
			$count = $db->clear_completed() + $db->clear_failed();
		}

		wp_send_json_success( array(
			'message' => sprintf( __( '%d items cleared.', 'inkbridge-gen' ), $count ),
		) );
	}

	// ─── Provider Testing ────────────────────────────────

	public function test_provider() {
		$this->verify_request();

		$provider_id   = sanitize_text_field( $_POST['provider_id'] ?? '' );
		$provider_type = sanitize_text_field( $_POST['provider_type'] ?? 'text' );
		$api_key       = sanitize_text_field( $_POST['api_key'] ?? '' );

		// Validate provider ID against known providers.
		$valid_providers = 'text' === $provider_type
			? Inkbridge_Gen_Provider_Factory::get_text_provider_ids()
			: Inkbridge_Gen_Provider_Factory::get_image_provider_ids();

		if ( ! in_array( $provider_id, $valid_providers, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Unknown provider.', 'inkbridge-gen' ) ) );
		}

		// If no key provided, try stored key.
		if ( ! $api_key ) {
			$settings = new Inkbridge_Gen_Settings();
			$api_key  = $settings->get_provider_api_key( $provider_id );
		}

		if ( ! $api_key ) {
			wp_send_json_error( array( 'message' => __( 'No API key provided.', 'inkbridge-gen' ) ) );
		}

		// Validate API key format: minimum length, printable ASCII only, no whitespace.
		if ( strlen( $api_key ) < 10 || preg_match( '/\s/', $api_key ) || ! preg_match( '/^[\x21-\x7E]+$/', $api_key ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid API key format.', 'inkbridge-gen' ) ) );
		}

		try {
			if ( 'text' === $provider_type ) {
				$provider = Inkbridge_Gen_Provider_Factory::create_text_provider( $provider_id, $api_key );
			} else {
				$provider = Inkbridge_Gen_Provider_Factory::create_image_provider( $provider_id, $api_key );
			}

			$result = $provider->test_connection();

			if ( true === $result ) {
				wp_send_json_success( array( 'message' => __( 'Connection successful!', 'inkbridge-gen' ) ) );
			} else {
				wp_send_json_error( array( 'message' => $result ) );
			}
		} catch ( \Throwable $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	// ─── Topic Suggestion ────────────────────────────────

	public function suggest_topic() {
		$this->verify_request();

		$pillar_key = sanitize_text_field( $_POST['pillar'] ?? '' );

		$settings    = new Inkbridge_Gen_Settings();
		$pillar_data = $pillar_key ? $settings->get_pillar( $pillar_key ) : null;

		$pillar_label   = $pillar_data['label'] ?? '';
		$pillar_context = $pillar_data['context'] ?? '';

		$system_prompt = 'You are a content strategist. Suggest a single, specific article topic. Reply with ONLY the topic text, nothing else — no quotes, no numbering, no explanation.';

		$user_prompt = 'Suggest one specific, engaging article topic';
		if ( $pillar_label ) {
			$user_prompt .= ' for the content pillar "' . $pillar_label . '"';
		}
		if ( $pillar_context ) {
			$user_prompt .= '. Pillar context: ' . $pillar_context;
		}
		$user_prompt .= '. The topic should be suitable for a blog post of around 1500 words.';

		try {
			$tp_id  = $settings->get_active_text_provider();
			$tp_key = $settings->get_provider_api_key( $tp_id );
			$tp_cfg = $settings->get_text_provider_config( $tp_id );
			$text_provider = Inkbridge_Gen_Provider_Factory::create_text_provider( $tp_id, $tp_key, $tp_cfg['model'] ?? '' );

			$result = $text_provider->generate( $system_prompt, $user_prompt, 100 );
			$topic  = trim( $result['content'], " \t\n\r\0\x0B\"'" );

			wp_send_json_success( array( 'topic' => $topic ) );
		} catch ( \Throwable $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	// ─── Settings ────────────────────────────────────────

	public function save_settings() {
		$this->verify_request();

		$section = sanitize_text_field( $_POST['section'] ?? '' );
		$data    = array();

		if ( ! empty( $_POST['settings'] ) ) {
			$data = json_decode( wp_unslash( $_POST['settings'] ), true ) ?: array();
		}

		if ( ! $section ) {
			wp_send_json_error( array( 'message' => __( 'No section specified.', 'inkbridge-gen' ) ) );
		}

		$settings = new Inkbridge_Gen_Settings();
		$settings->save( $section, $data );

		// Reschedule cron if scheduling settings changed.
		if ( 'scheduling' === $section ) {
			$scheduler = new Inkbridge_Gen_Scheduler();
			$scheduler->unschedule_events();
			$scheduler->schedule_events();
		}

		wp_send_json_success( array( 'message' => __( 'Settings saved.', 'inkbridge-gen' ) ) );
	}

	// ─── Helpers ─────────────────────────────────────────

	private function verify_request() {
		if ( ! check_ajax_referer( 'inkbridge_gen_admin', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'inkbridge-gen' ) ), 403 );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'inkbridge-gen' ) ), 403 );
		}
	}
}
