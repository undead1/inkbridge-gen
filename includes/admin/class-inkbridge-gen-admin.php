<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Inkbridge_Gen_Admin {

	private $hook_prefix = '';

	public function register_menu() {
		$this->hook_prefix = add_menu_page(
			__( 'Inkbridge Generator', 'inkbridge-gen' ),
			__( 'Inkbridge Generator', 'inkbridge-gen' ),
			'manage_options',
			'inkbridge-gen',
			array( $this, 'render_dashboard' ),
			'dashicons-edit-page',
			30
		);

		add_submenu_page( 'inkbridge-gen', __( 'Dashboard', 'inkbridge-gen' ), __( 'Dashboard', 'inkbridge-gen' ), 'manage_options', 'inkbridge-gen', array( $this, 'render_dashboard' ) );
		add_submenu_page( 'inkbridge-gen', __( 'Generate', 'inkbridge-gen' ), __( 'Generate', 'inkbridge-gen' ), 'manage_options', 'inkbridge-gen-generate', array( $this, 'render_generate' ) );
		add_submenu_page( 'inkbridge-gen', __( 'Queue', 'inkbridge-gen' ), __( 'Queue', 'inkbridge-gen' ), 'manage_options', 'inkbridge-gen-queue', array( $this, 'render_queue' ) );
		add_submenu_page( 'inkbridge-gen', __( 'Settings', 'inkbridge-gen' ), __( 'Settings', 'inkbridge-gen' ), 'manage_options', 'inkbridge-gen-settings', array( $this, 'render_settings' ) );
		add_submenu_page( 'inkbridge-gen', __( 'Logs', 'inkbridge-gen' ), __( 'Logs', 'inkbridge-gen' ), 'manage_options', 'inkbridge-gen-logs', array( $this, 'render_logs' ) );
	}

	public function enqueue_assets( $hook ) {
		if ( ! $this->is_inkbridge_gen_page( $hook ) ) {
			return;
		}

		wp_enqueue_style(
			'inkbridge-gen-admin',
			INKBRIDGE_GEN_URL . 'admin/css/inkbridge-gen-admin.css',
			array(),
			INKBRIDGE_GEN_VERSION
		);

		wp_enqueue_script(
			'inkbridge-gen-admin',
			INKBRIDGE_GEN_URL . 'admin/js/inkbridge-gen-admin.js',
			array( 'jquery' ),
			INKBRIDGE_GEN_VERSION,
			true
		);

		wp_localize_script( 'inkbridge-gen-admin', 'inkbridgeGen', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'inkbridge_gen_admin' ),
			'strings' => array(
				'confirm_delete'    => __( 'Are you sure you want to delete this item?', 'inkbridge-gen' ),
				'confirm_clear'     => __( 'Are you sure you want to clear these items?', 'inkbridge-gen' ),
				'generate'          => __( 'Generate Article', 'inkbridge-gen' ),
				'generating'        => __( 'Generating...', 'inkbridge-gen' ),
				'queued'            => __( 'Queued for generation...', 'inkbridge-gen' ),
				'processing'        => __( 'Generating article in background...', 'inkbridge-gen' ),
				'translating'       => __( 'Translating to', 'inkbridge-gen' ),
				'fetching_image'    => __( 'Fetching featured image...', 'inkbridge-gen' ),
				'publishing'        => __( 'Publishing to WordPress...', 'inkbridge-gen' ),
				'complete'          => __( 'Complete!', 'inkbridge-gen' ),
				'error'             => __( 'Error', 'inkbridge-gen' ),
				'testing'           => __( 'Testing...', 'inkbridge-gen' ),
				'connection_ok'     => __( 'Connection OK', 'inkbridge-gen' ),
				'connection_failed' => __( 'Connection failed', 'inkbridge-gen' ),
				'saved'             => __( 'Settings saved.', 'inkbridge-gen' ),
				'imported'          => __( 'Topics imported.', 'inkbridge-gen' ),
				'suggest_topic'     => __( 'Suggest Topic', 'inkbridge-gen' ),
				'suggesting'        => __( 'Suggesting...', 'inkbridge-gen' ),
				'suggest_error'     => __( 'Could not suggest a topic.', 'inkbridge-gen' ),
			),
		) );

		// Page-specific scripts.
		$page = sanitize_text_field( $_GET['page'] ?? '' );
		if ( 'inkbridge-gen-generate' === $page ) {
			wp_enqueue_script( 'inkbridge-gen-generate', INKBRIDGE_GEN_URL . 'admin/js/inkbridge-gen-generate.js', array( 'jquery', 'inkbridge-gen-admin' ), INKBRIDGE_GEN_VERSION, true );
		} elseif ( 'inkbridge-gen-queue' === $page ) {
			wp_enqueue_script( 'inkbridge-gen-queue', INKBRIDGE_GEN_URL . 'admin/js/inkbridge-gen-queue.js', array( 'jquery', 'inkbridge-gen-admin' ), INKBRIDGE_GEN_VERSION, true );
		} elseif ( 'inkbridge-gen-settings' === $page ) {
			wp_enqueue_script( 'inkbridge-gen-settings', INKBRIDGE_GEN_URL . 'admin/js/inkbridge-gen-settings.js', array( 'jquery', 'inkbridge-gen-admin' ), INKBRIDGE_GEN_VERSION, true );
		}
	}

	public function render_dashboard() {
		include INKBRIDGE_GEN_DIR . 'admin/views/page-dashboard.php';
	}

	public function render_generate() {
		include INKBRIDGE_GEN_DIR . 'admin/views/page-generate.php';
	}

	public function render_queue() {
		include INKBRIDGE_GEN_DIR . 'admin/views/page-queue.php';
	}

	public function render_settings() {
		include INKBRIDGE_GEN_DIR . 'admin/views/page-settings.php';
	}

	public function render_logs() {
		include INKBRIDGE_GEN_DIR . 'admin/views/page-logs.php';
	}

	private function is_inkbridge_gen_page( $hook ) {
		$page = sanitize_text_field( $_GET['page'] ?? '' );
		return str_starts_with( $page, 'inkbridge-gen' );
	}
}
