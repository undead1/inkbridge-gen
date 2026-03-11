<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<?php
$active_image_provider = $settings->get_active_image_provider();
$image_orientation     = $settings->get( 'image_orientation', 'landscape' );
$search_suffix         = $settings->get( 'image_search_suffix', '' );

$providers = array(
	'unsplash'      => array(
		'label'     => 'Unsplash',
		'help_url'  => 'https://unsplash.com/developers',
		'help_text' => __( 'Register a free developer account and use the Access Key (not the Secret Key). Free for up to 50 requests/hour.', 'inkbridge-gen' ),
	),
	'shutterstock'  => array(
		'label'     => 'Shutterstock',
		'help_url'  => 'https://www.shutterstock.com/developers',
		'help_text' => __( 'Create a developer account and generate a Bearer token. Requires an active subscription for licensed downloads.', 'inkbridge-gen' ),
	),
	'depositphotos' => array(
		'label'     => 'DepositPhotos',
		'help_url'  => 'https://depositphotos.com/api-integration.html',
		'help_text' => __( 'Apply for API access via the integration page. Requires a deposit or subscription for licensed downloads.', 'inkbridge-gen' ),
	),
);
?>
<form class="inkbridge-gen-settings-form" data-section="image_providers">
	<?php wp_nonce_field( 'inkbridge_gen_admin', '_inkbridge_gen_nonce' ); ?>

	<!-- Active Provider -->
	<h3><?php esc_html_e( 'Active Image Provider', 'inkbridge-gen' ); ?></h3>
	<fieldset>
		<?php foreach ( $providers as $pid => $info ) : ?>
			<label style="display: inline-block; margin-right: 20px;">
				<input type="radio" name="active_image_provider" value="<?php echo esc_attr( $pid ); ?>"
					<?php checked( $active_image_provider, $pid ); ?> />
				<?php echo esc_html( $info['label'] ); ?>
			</label>
		<?php endforeach; ?>
	</fieldset>

	<hr />

	<!-- Provider API Keys -->
	<?php foreach ( $providers as $pid => $info ) :
		$has_key = $settings->has_provider_api_key( $pid );
	?>
		<div class="inkbridge-gen-provider-config postbox">
			<h2 class="hndle"><span><?php echo esc_html( $info['label'] ); ?></span></h2>
			<div class="inside">
				<table class="form-table">
					<!-- API Key -->
					<tr>
						<th scope="row">
							<label for="inkbridge-gen-image-key-<?php echo esc_attr( $pid ); ?>"><?php esc_html_e( 'API Key', 'inkbridge-gen' ); ?></label>
						</th>
						<td>
							<input type="password" id="inkbridge-gen-image-key-<?php echo esc_attr( $pid ); ?>"
								   name="api_keys[<?php echo esc_attr( $pid ); ?>]"
								   data-provider="<?php echo esc_attr( $pid ); ?>"
								   class="regular-text"
								   placeholder="<?php echo $has_key ? '' : esc_attr__( 'Enter API key', 'inkbridge-gen' ); ?>" />
							<?php if ( $has_key ) : ?>
								<span class="inkbridge-gen-key-status" style="color: green; margin-left: 8px;">
									<?php esc_html_e( 'API key is set', 'inkbridge-gen' ); ?>
								</span>
							<?php endif; ?>
							<p class="description">
								<?php esc_html_e( 'Leave blank to keep existing key.', 'inkbridge-gen' ); ?>
								<br />
								<?php echo esc_html( $info['help_text'] ); ?>
								<a href="<?php echo esc_url( $info['help_url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Get API key &rarr;', 'inkbridge-gen' ); ?></a>
							</p>
						</td>
					</tr>

					<!-- Test Connection -->
					<tr>
						<th scope="row"></th>
						<td>
							<button type="button" class="button button-secondary inkbridge-gen-test-provider"
									data-provider="<?php echo esc_attr( $pid ); ?>" data-type="image">
								<?php esc_html_e( 'Test Connection', 'inkbridge-gen' ); ?>
							</button>
							<span class="inkbridge-gen-test-result" data-provider="<?php echo esc_attr( $pid ); ?>"></span>
						</td>
					</tr>
				</table>
			</div>
		</div>
	<?php endforeach; ?>

	<hr />

	<!-- Default Orientation -->
	<h3><?php esc_html_e( 'Default Orientation', 'inkbridge-gen' ); ?></h3>
	<fieldset>
		<label style="display: inline-block; margin-right: 20px;">
			<input type="radio" name="image_orientation" value="landscape" <?php checked( $image_orientation, 'landscape' ); ?> />
			<?php esc_html_e( 'Landscape', 'inkbridge-gen' ); ?>
		</label>
		<label style="display: inline-block; margin-right: 20px;">
			<input type="radio" name="image_orientation" value="portrait" <?php checked( $image_orientation, 'portrait' ); ?> />
			<?php esc_html_e( 'Portrait', 'inkbridge-gen' ); ?>
		</label>
		<label style="display: inline-block; margin-right: 20px;">
			<input type="radio" name="image_orientation" value="squarish" <?php checked( $image_orientation, 'squarish' ); ?> />
			<?php esc_html_e( 'Square', 'inkbridge-gen' ); ?>
		</label>
	</fieldset>

	<!-- Search Suffix -->
	<table class="form-table">
		<tr>
			<th scope="row">
				<label for="inkbridge-gen-search-suffix"><?php esc_html_e( 'Search Suffix', 'inkbridge-gen' ); ?></label>
			</th>
			<td>
				<input type="text" id="inkbridge-gen-search-suffix" name="image_search_suffix"
					   class="regular-text" value="<?php echo esc_attr( $search_suffix ); ?>" />
				<p class="description"><?php esc_html_e( 'Text appended to every image search query (e.g. "high quality photography").', 'inkbridge-gen' ); ?></p>
			</td>
		</tr>
	</table>

	<p class="submit">
		<button type="button" class="inkbridge-gen-save-settings button button-primary" data-section="image_providers">
			<?php esc_html_e( 'Save Settings', 'inkbridge-gen' ); ?>
		</button>
	</p>

</form>
