<?php
/**
 * Main plugin class.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Inkbridge_Gen {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
		Inkbridge_Gen_DB::maybe_upgrade();
	}

	private function load_dependencies() {
		$dir = INKBRIDGE_GEN_DIR . 'includes/';

		// Activator / Deactivator.
		require_once $dir . 'class-inkbridge-gen-activator.php';
		require_once $dir . 'class-inkbridge-gen-deactivator.php';

		// Database.
		require_once $dir . 'db/class-inkbridge-gen-db.php';
		require_once $dir . 'db/class-inkbridge-gen-logger.php';

		// Settings.
		require_once $dir . 'admin/class-inkbridge-gen-settings.php';

		// Providers.
		require_once $dir . 'providers/interface-inkbridge-gen-text-provider.php';
		require_once $dir . 'providers/interface-inkbridge-gen-image-provider.php';
		require_once $dir . 'providers/class-inkbridge-gen-provider-factory.php';
		require_once $dir . 'providers/text/class-inkbridge-gen-openai.php';
		require_once $dir . 'providers/text/class-inkbridge-gen-claude.php';
		require_once $dir . 'providers/text/class-inkbridge-gen-gemini.php';
		require_once $dir . 'providers/image/class-inkbridge-gen-unsplash.php';
		require_once $dir . 'providers/image/class-inkbridge-gen-shutterstock.php';
		require_once $dir . 'providers/image/class-inkbridge-gen-depositphotos.php';

		// Core engine.
		require_once $dir . 'core/class-inkbridge-gen-generator.php';
		require_once $dir . 'core/class-inkbridge-gen-translator.php';
		require_once $dir . 'core/class-inkbridge-gen-publisher.php';
		require_once $dir . 'core/class-inkbridge-gen-image-handler.php';
		require_once $dir . 'core/class-inkbridge-gen-pipeline.php';
		require_once $dir . 'core/class-inkbridge-gen-queue-processor.php';

		// Cron.
		require_once $dir . 'cron/class-inkbridge-gen-scheduler.php';

		// Admin.
		if ( is_admin() ) {
			require_once $dir . 'admin/class-inkbridge-gen-admin.php';
			require_once $dir . 'admin/class-inkbridge-gen-admin-ajax.php';
			require_once $dir . 'admin/class-inkbridge-gen-dashboard.php';
			require_once $dir . 'admin/class-inkbridge-gen-list-table.php';
		}
	}

	private function init_hooks() {
		if ( is_admin() ) {
			$admin = new Inkbridge_Gen_Admin();
			add_action( 'admin_menu', array( $admin, 'register_menu' ) );
			add_action( 'admin_enqueue_scripts', array( $admin, 'enqueue_assets' ) );

			new Inkbridge_Gen_Admin_Ajax();
		}

		$scheduler = new Inkbridge_Gen_Scheduler();
		add_filter( 'cron_schedules', array( $scheduler, 'register_cron_schedules' ) );
		add_action( 'inkbridge_gen_process_queue', array( $scheduler, 'handle_queue_processing' ) );
		add_action( 'inkbridge_gen_auto_generate', array( $scheduler, 'handle_auto_generate' ) );
		add_action( 'inkbridge_gen_cleanup_logs', array( $scheduler, 'handle_log_cleanup' ) );
	}
}
