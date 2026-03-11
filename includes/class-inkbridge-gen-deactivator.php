<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Inkbridge_Gen_Deactivator {

	public static function deactivate() {
		wp_clear_scheduled_hook( 'inkbridge_gen_process_queue' );
		wp_clear_scheduled_hook( 'inkbridge_gen_auto_generate' );
		wp_clear_scheduled_hook( 'inkbridge_gen_cleanup_logs' );
	}
}
