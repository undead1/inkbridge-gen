<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<?php
$active_text_provider = $settings->get_active_text_provider();

$providers = array(
	'openai' => array(
		'label'    => 'OpenAI',
		'models'   => array( 'gpt-4o-mini', 'gpt-4o', 'gpt-4.1-nano', 'gpt-4.1-mini', 'gpt-4.1' ),
		'help_url' => 'https://platform.openai.com/api-keys',
		'help_text' => __( 'Get your API key from the OpenAI Platform. Requires a paid account with usage-based billing.', 'inkbridge-gen' ),
	),
	'claude' => array(
		'label'    => 'Claude',
		'models'   => array( 'claude-sonnet-4-20250514', 'claude-haiku-4-5-20251001' ),
		'help_url' => 'https://console.anthropic.com/settings/keys',
		'help_text' => __( 'Get your API key from the Anthropic Console. Requires a paid account with usage-based billing.', 'inkbridge-gen' ),
	),
	'gemini' => array(
		'label'    => 'Gemini',
		'models'   => array( 'gemini-2.0-flash', 'gemini-2.5-pro', 'gemini-2.5-flash' ),
		'help_url' => 'https://aistudio.google.com/apikey',
		'help_text' => __( 'Get your API key from Google AI Studio. Free tier available with generous limits.', 'inkbridge-gen' ),
	),
);
?>
<form class="inkbridge-gen-settings-form" data-section="text_providers">
	<?php wp_nonce_field( 'inkbridge_gen_admin', '_inkbridge_gen_nonce' ); ?>

	<!-- Active Provider -->
	<h3><?php esc_html_e( 'Active Text Provider', 'inkbridge-gen' ); ?></h3>
	<fieldset>
		<?php foreach ( $providers as $pid => $info ) : ?>
			<label style="display: inline-block; margin-right: 20px;">
				<input type="radio" name="active_text_provider" value="<?php echo esc_attr( $pid ); ?>"
					<?php checked( $active_text_provider, $pid ); ?> />
				<?php echo esc_html( $info['label'] ); ?>
			</label>
		<?php endforeach; ?>
	</fieldset>

	<hr />

	<!-- Provider Configuration -->
	<?php foreach ( $providers as $pid => $info ) :
		$config     = $settings->get_text_provider_config( $pid );
		$has_key    = $settings->has_provider_api_key( $pid );
		$cur_model  = $config['model'] ?? '';
		$max_tokens = $config['max_tokens'] ?? 4096;
	?>
		<div class="inkbridge-gen-provider-config postbox">
			<h2 class="hndle"><span><?php echo esc_html( $info['label'] ); ?></span></h2>
			<div class="inside">
				<table class="form-table">
					<!-- API Key -->
					<tr>
						<th scope="row">
							<label for="inkbridge-gen-api-key-<?php echo esc_attr( $pid ); ?>"><?php esc_html_e( 'API Key', 'inkbridge-gen' ); ?></label>
						</th>
						<td>
							<input type="password" id="inkbridge-gen-api-key-<?php echo esc_attr( $pid ); ?>"
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
								<?php esc_html_e( 'Leave blank to keep existing key. Enter a new key to update.', 'inkbridge-gen' ); ?>
								<br />
								<?php echo esc_html( $info['help_text'] ); ?>
								<a href="<?php echo esc_url( $info['help_url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Get API key &rarr;', 'inkbridge-gen' ); ?></a>
							</p>
						</td>
					</tr>

					<!-- Model -->
					<tr>
						<th scope="row">
							<label for="inkbridge-gen-model-<?php echo esc_attr( $pid ); ?>"><?php esc_html_e( 'Model', 'inkbridge-gen' ); ?></label>
						</th>
						<td>
							<select id="inkbridge-gen-model-<?php echo esc_attr( $pid ); ?>"
									name="text_providers[<?php echo esc_attr( $pid ); ?>][model]">
								<?php foreach ( $info['models'] as $model ) : ?>
									<option value="<?php echo esc_attr( $model ); ?>" <?php selected( $cur_model, $model ); ?>>
										<?php echo esc_html( $model ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>

					<!-- Max Tokens -->
					<tr>
						<th scope="row">
							<label for="inkbridge-gen-max-tokens-<?php echo esc_attr( $pid ); ?>"><?php esc_html_e( 'Max Tokens', 'inkbridge-gen' ); ?></label>
						</th>
						<td>
							<input type="number" id="inkbridge-gen-max-tokens-<?php echo esc_attr( $pid ); ?>"
								   name="text_providers[<?php echo esc_attr( $pid ); ?>][max_tokens]"
								   class="small-text" value="<?php echo esc_attr( $max_tokens ); ?>"
								   min="256" max="128000" step="256" />
						</td>
					</tr>

					<!-- Test Connection -->
					<tr>
						<th scope="row"></th>
						<td>
							<button type="button" class="button button-secondary inkbridge-gen-test-provider"
									data-provider="<?php echo esc_attr( $pid ); ?>">
								<?php esc_html_e( 'Test Connection', 'inkbridge-gen' ); ?>
							</button>
							<span class="inkbridge-gen-test-result" data-provider="<?php echo esc_attr( $pid ); ?>"></span>
						</td>
					</tr>
				</table>
			</div>
		</div>
	<?php endforeach; ?>

	<p class="submit">
		<button type="button" class="inkbridge-gen-save-settings button button-primary" data-section="text_providers">
			<?php esc_html_e( 'Save Settings', 'inkbridge-gen' ); ?>
		</button>
	</p>

</form>
