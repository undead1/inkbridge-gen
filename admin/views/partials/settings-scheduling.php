<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<?php
$cron_enabled     = $settings->get( 'cron_enabled', false );
$cron_frequency   = $settings->get( 'cron_frequency', 'daily' );
$cron_max_per_run = $settings->get( 'cron_max_per_run', 1 );

$autogen_enabled     = $settings->get( 'autogen_enabled', false );
$autogen_pillars     = $settings->get( 'autogen_pillars', array() );
$autogen_frequency   = $settings->get( 'autogen_frequency', 'daily' );
$autogen_time        = $settings->get( 'autogen_time', '09:00' );
$autogen_count       = $settings->get( 'autogen_count', 1 );
$autogen_word_count  = $settings->get( 'autogen_word_count', $settings->get( 'default_word_count', 1500 ) );
$autogen_post_status = $settings->get( 'autogen_post_status', $settings->get( 'default_post_status', 'draft' ) );
$all_pillars         = $settings->get_pillars();
$wp_timezone_string  = wp_timezone_string();

$system_cron_cmd = $settings->get_system_cron_command();
$wp_cli_cron_cmd = $settings->get_wp_cli_cron_command();
?>
<form class="inkbridge-gen-settings-form" data-section="scheduling">
	<?php wp_nonce_field( 'inkbridge_gen_admin', '_inkbridge_gen_nonce' ); ?>

	<table class="form-table">

		<!-- Enable WP-Cron -->
		<tr>
			<th scope="row"><?php esc_html_e( 'WP-Cron Queue Processing', 'inkbridge-gen' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="cron_enabled" value="1" <?php checked( $cron_enabled ); ?> />
					<?php esc_html_e( 'Enable automatic queue processing via WP-Cron', 'inkbridge-gen' ); ?>
				</label>
			</td>
		</tr>

		<!-- Frequency -->
		<tr>
			<th scope="row">
				<label for="inkbridge-gen-cron-frequency"><?php esc_html_e( 'Frequency', 'inkbridge-gen' ); ?></label>
			</th>
			<td>
				<select id="inkbridge-gen-cron-frequency" name="cron_frequency">
					<option value="hourly" <?php selected( $cron_frequency, 'hourly' ); ?>><?php esc_html_e( 'Every Hour', 'inkbridge-gen' ); ?></option>
					<option value="twicedaily" <?php selected( $cron_frequency, 'twicedaily' ); ?>><?php esc_html_e( 'Twice Daily', 'inkbridge-gen' ); ?></option>
					<option value="daily" <?php selected( $cron_frequency, 'daily' ); ?>><?php esc_html_e( 'Daily', 'inkbridge-gen' ); ?></option>
					<option value="weekly" <?php selected( $cron_frequency, 'weekly' ); ?>><?php esc_html_e( 'Weekly', 'inkbridge-gen' ); ?></option>
				</select>
			</td>
		</tr>

		<!-- Max Per Run -->
		<tr>
			<th scope="row">
				<label for="inkbridge-gen-cron-max"><?php esc_html_e( 'Max Per Run', 'inkbridge-gen' ); ?></label>
			</th>
			<td>
				<input type="number" id="inkbridge-gen-cron-max" name="cron_max_per_run"
					   class="small-text" value="<?php echo esc_attr( $cron_max_per_run ); ?>"
					   min="1" max="50" step="1" />
				<p class="description"><?php esc_html_e( 'Maximum number of queue items to process per scheduled run.', 'inkbridge-gen' ); ?></p>
			</td>
		</tr>

	</table>

	<hr />

	<!-- Auto-Generate Section -->
	<h3><?php esc_html_e( 'Auto-Generate Articles', 'inkbridge-gen' ); ?></h3>
	<p class="description"><?php esc_html_e( 'Automatically generate article topics using AI and add them to the queue on a schedule.', 'inkbridge-gen' ); ?></p>

	<table class="form-table">

		<tr>
			<th scope="row"><?php esc_html_e( 'Enable Auto-Generate', 'inkbridge-gen' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="autogen_enabled" value="1" <?php checked( $autogen_enabled ); ?> />
					<?php esc_html_e( 'Automatically suggest topics and queue articles for generation', 'inkbridge-gen' ); ?>
				</label>
			</td>
		</tr>

		<tr>
			<th scope="row"><?php esc_html_e( 'Content Pillars', 'inkbridge-gen' ); ?></th>
			<td>
				<?php if ( empty( $all_pillars ) ) : ?>
					<p class="description" style="color:#d63638"><?php esc_html_e( 'No pillars configured. Add pillars in the Categories tab first.', 'inkbridge-gen' ); ?></p>
				<?php else : ?>
					<fieldset>
						<?php foreach ( $all_pillars as $pillar ) : ?>
							<label style="display:block;margin-bottom:4px">
								<input type="checkbox" name="autogen_pillars[]"
									   value="<?php echo esc_attr( $pillar['key'] ); ?>"
									   <?php checked( in_array( $pillar['key'], $autogen_pillars, true ) ); ?> />
								<?php echo esc_html( $pillar['label'] ); ?>
							</label>
						<?php endforeach; ?>
					</fieldset>
					<p class="description"><?php esc_html_e( 'Select which pillars to rotate through when auto-generating topics.', 'inkbridge-gen' ); ?></p>
				<?php endif; ?>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label for="inkbridge-gen-autogen-frequency"><?php esc_html_e( 'Frequency', 'inkbridge-gen' ); ?></label>
			</th>
			<td>
				<select id="inkbridge-gen-autogen-frequency" name="autogen_frequency">
					<option value="twicedaily" <?php selected( $autogen_frequency, 'twicedaily' ); ?>><?php esc_html_e( 'Twice Daily', 'inkbridge-gen' ); ?></option>
					<option value="daily" <?php selected( $autogen_frequency, 'daily' ); ?>><?php esc_html_e( 'Daily', 'inkbridge-gen' ); ?></option>
					<option value="weekly" <?php selected( $autogen_frequency, 'weekly' ); ?>><?php esc_html_e( 'Weekly', 'inkbridge-gen' ); ?></option>
				</select>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label for="inkbridge-gen-autogen-time"><?php esc_html_e( 'Start Time', 'inkbridge-gen' ); ?></label>
			</th>
			<td>
				<input type="time" id="inkbridge-gen-autogen-time" name="autogen_time"
					   value="<?php echo esc_attr( $autogen_time ); ?>" />
				<p class="description">
					<?php
					printf(
						/* translators: %s: timezone string */
						esc_html__( 'Time of day to run (timezone: %s).', 'inkbridge-gen' ),
						'<strong>' . esc_html( $wp_timezone_string ) . '</strong>'
					);
					?>
				</p>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label for="inkbridge-gen-autogen-count"><?php esc_html_e( 'Articles Per Run', 'inkbridge-gen' ); ?></label>
			</th>
			<td>
				<input type="number" id="inkbridge-gen-autogen-count" name="autogen_count"
					   class="small-text" value="<?php echo esc_attr( $autogen_count ); ?>"
					   min="1" max="10" step="1" />
				<p class="description"><?php esc_html_e( 'Number of articles to queue per scheduled run.', 'inkbridge-gen' ); ?></p>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label for="inkbridge-gen-autogen-word-count"><?php esc_html_e( 'Word Count', 'inkbridge-gen' ); ?></label>
			</th>
			<td>
				<input type="number" id="inkbridge-gen-autogen-word-count" name="autogen_word_count"
					   class="small-text" value="<?php echo esc_attr( $autogen_word_count ); ?>"
					   min="300" max="10000" step="100" />
			</td>
		</tr>

		<tr>
			<th scope="row"><?php esc_html_e( 'Post Status', 'inkbridge-gen' ); ?></th>
			<td>
				<label style="margin-right:16px">
					<input type="radio" name="autogen_post_status" value="draft" <?php checked( $autogen_post_status, 'draft' ); ?> />
					<?php esc_html_e( 'Draft', 'inkbridge-gen' ); ?>
				</label>
				<label>
					<input type="radio" name="autogen_post_status" value="publish" <?php checked( $autogen_post_status, 'publish' ); ?> />
					<?php esc_html_e( 'Publish', 'inkbridge-gen' ); ?>
				</label>
			</td>
		</tr>

	</table>

	<hr />

	<!-- System Cron Section -->
	<h3><?php esc_html_e( 'System Cron (Recommended)', 'inkbridge-gen' ); ?></h3>
	<p class="description">
		<?php esc_html_e( 'For more reliable scheduling, disable WP-Cron and use your server\'s cron system instead. Add one of these entries to your crontab:', 'inkbridge-gen' ); ?>
	</p>

	<h4><?php esc_html_e( 'WP-Cron via PHP', 'inkbridge-gen' ); ?></h4>
	<pre><code readonly><?php echo esc_html( $system_cron_cmd ); ?></code></pre>

	<h4><?php esc_html_e( 'WP-CLI (Preferred)', 'inkbridge-gen' ); ?></h4>
	<pre><code readonly><?php echo esc_html( $wp_cli_cron_cmd ); ?></code></pre>

	<div class="notice notice-info inline" style="margin-top: 15px;">
		<p>
			<?php
			printf(
				/* translators: %s: wp-config.php constant */
				esc_html__( 'When using system cron, add %s to your wp-config.php to disable the built-in WP-Cron.', 'inkbridge-gen' ),
				'<code>define( \'DISABLE_WP_CRON\', true );</code>'
			);
			?>
		</p>
	</div>

	<p class="submit">
		<button type="button" class="inkbridge-gen-save-settings button button-primary" data-section="scheduling">
			<?php esc_html_e( 'Save Settings', 'inkbridge-gen' ); ?>
		</button>
	</p>

</form>
