<?php
/**
 * Plugin Name: Inkbridge Generator
 * Plugin URI: https://github.com/undead1/inkbridge-gen
 * Description: AI-powered content generation, translation, and publishing pipeline for WordPress. Supports OpenAI, Claude, and Gemini.
 * Version: 1.4.2
 * Author: Inkbridge Generator
 * Author URI: https://github.com/undead1/inkbridge-gen
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: inkbridge-gen
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'INKBRIDGE_GEN_VERSION', '1.4.2' );
define( 'INKBRIDGE_GEN_FILE', __FILE__ );
define( 'INKBRIDGE_GEN_DIR', plugin_dir_path( __FILE__ ) );
define( 'INKBRIDGE_GEN_URL', plugin_dir_url( __FILE__ ) );
define( 'INKBRIDGE_GEN_BASENAME', plugin_basename( __FILE__ ) );

// Activation and deactivation hooks.
register_activation_hook( __FILE__, array( 'Inkbridge_Gen_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Inkbridge_Gen_Deactivator', 'deactivate' ) );

require_once INKBRIDGE_GEN_DIR . 'includes/class-inkbridge-gen.php';

/**
 * Returns the main plugin instance.
 *
 * @return Inkbridge_Gen
 */
function inkbridge_gen() {
	return Inkbridge_Gen::instance();
}

inkbridge_gen();

// Auto-updates from GitHub.
if ( file_exists( INKBRIDGE_GEN_DIR . 'vendor/autoload.php' ) ) {
	require_once INKBRIDGE_GEN_DIR . 'vendor/autoload.php';
	$inkbridge_gen_updater = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
		'https://github.com/undead1/inkbridge-gen/',
		__FILE__,
		'inkbridge-gen'
	);
	$inkbridge_gen_updater->setBranch( 'main' );
	$inkbridge_gen_updater->getVcsApi()->enableReleaseAssets();
}
