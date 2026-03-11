<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Drop tables.
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}inkbridge_gen_logs" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}inkbridge_gen_queue" );

// Delete options.
delete_option( 'inkbridge_gen_settings' );
delete_option( 'inkbridge_gen_db_version' );
delete_option( 'inkbridge_gen_api_key_openai' );
delete_option( 'inkbridge_gen_api_key_claude' );
delete_option( 'inkbridge_gen_api_key_gemini' );
delete_option( 'inkbridge_gen_api_key_unsplash' );
delete_option( 'inkbridge_gen_api_key_shutterstock' );
delete_option( 'inkbridge_gen_api_key_depositphotos' );
delete_option( 'inkbridge_gen_autogen_pillar_index' );

// Clear cron.
wp_clear_scheduled_hook( 'inkbridge_gen_process_queue' );
wp_clear_scheduled_hook( 'inkbridge_gen_auto_generate' );
wp_clear_scheduled_hook( 'inkbridge_gen_cleanup_logs' );
