<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<?php if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Permission denied.', 'inkbridge-gen' ) ); } ?>
<?php
$settings = new Inkbridge_Gen_Settings();

$tabs = array(
	'general'         => __( 'General', 'inkbridge-gen' ),
	'text_providers'  => __( 'Text Providers', 'inkbridge-gen' ),
	'image_providers' => __( 'Image Providers', 'inkbridge-gen' ),
	'languages'       => __( 'Languages', 'inkbridge-gen' ),
	'pillars'         => __( 'Categories', 'inkbridge-gen' ),
	'prompts'         => __( 'Prompts', 'inkbridge-gen' ),
	'scheduling'      => __( 'Scheduling', 'inkbridge-gen' ),
);

$active_tab = sanitize_text_field( $_GET['tab'] ?? 'general' );
if ( ! array_key_exists( $active_tab, $tabs ) ) {
	$active_tab = 'general';
}

$tab_to_partial = array(
	'general'         => 'settings-general.php',
	'text_providers'  => 'settings-text-providers.php',
	'image_providers' => 'settings-image-providers.php',
	'languages'       => 'settings-languages.php',
	'pillars'         => 'settings-pillars.php',
	'prompts'         => 'settings-prompts.php',
	'scheduling'      => 'settings-scheduling.php',
);
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Inkbridge Generator Settings', 'inkbridge-gen' ); ?></h1>

	<!-- Horizontal Sub-Tabs -->
	<nav class="nav-tab-wrapper">
		<?php foreach ( $tabs as $tab_key => $tab_label ) : ?>
			<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'inkbridge-gen-settings', 'tab' => $tab_key ), admin_url( 'admin.php' ) ) ); ?>"
			   class="nav-tab <?php echo $active_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
				<?php echo esc_html( $tab_label ); ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<div class="inkbridge-gen-settings-content" style="margin-top: 20px;">
		<?php
		$partial_file = INKBRIDGE_GEN_DIR . 'admin/views/partials/' . $tab_to_partial[ $active_tab ];
		if ( file_exists( $partial_file ) ) {
			include $partial_file;
		}
		?>
	</div>

</div>
