<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<?php
$default_word_count  = $settings->get( 'default_word_count', 1500 );
$default_post_status = $settings->get( 'default_post_status', 'draft' );
$default_author_id   = $settings->get( 'default_author_id', 1 );
$schedule_delay      = $settings->get( 'schedule_delay_hours', 24 );
$translation_meta    = $settings->get( 'translation_meta_key', '_inkbridge_gen_translations' );

$authors = get_users( array( 'capability' => 'publish_posts' ) );
?>
<form class="inkbridge-gen-settings-form" data-section="general">
	<?php wp_nonce_field( 'inkbridge_gen_admin', '_inkbridge_gen_nonce' ); ?>

	<table class="form-table">
		<!-- Default Word Count -->
		<tr>
			<th scope="row">
				<label for="inkbridge-gen-default-word-count"><?php esc_html_e( 'Default Word Count', 'inkbridge-gen' ); ?></label>
			</th>
			<td>
				<input type="number" id="inkbridge-gen-default-word-count" name="default_word_count"
					   class="small-text" value="<?php echo esc_attr( $default_word_count ); ?>"
					   min="100" max="10000" step="100" />
			</td>
		</tr>

		<!-- Default Post Status -->
		<tr>
			<th scope="row">
				<label for="inkbridge-gen-default-post-status"><?php esc_html_e( 'Default Post Status', 'inkbridge-gen' ); ?></label>
			</th>
			<td>
				<select id="inkbridge-gen-default-post-status" name="default_post_status">
					<option value="draft" <?php selected( $default_post_status, 'draft' ); ?>><?php esc_html_e( 'Draft', 'inkbridge-gen' ); ?></option>
					<option value="publish" <?php selected( $default_post_status, 'publish' ); ?>><?php esc_html_e( 'Publish', 'inkbridge-gen' ); ?></option>
					<option value="future" <?php selected( $default_post_status, 'future' ); ?>><?php esc_html_e( 'Scheduled', 'inkbridge-gen' ); ?></option>
				</select>
			</td>
		</tr>

		<!-- Default Author -->
		<tr>
			<th scope="row">
				<label for="inkbridge-gen-default-author"><?php esc_html_e( 'Default Author', 'inkbridge-gen' ); ?></label>
			</th>
			<td>
				<select id="inkbridge-gen-default-author" name="default_author_id">
					<?php foreach ( $authors as $author ) : ?>
						<option value="<?php echo esc_attr( $author->ID ); ?>" <?php selected( $default_author_id, $author->ID ); ?>>
							<?php echo esc_html( $author->display_name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>

		<!-- Schedule Delay Hours -->
		<tr>
			<th scope="row">
				<label for="inkbridge-gen-schedule-delay"><?php esc_html_e( 'Schedule Delay Hours', 'inkbridge-gen' ); ?></label>
			</th>
			<td>
				<input type="number" id="inkbridge-gen-schedule-delay" name="schedule_delay_hours"
					   class="small-text" value="<?php echo esc_attr( $schedule_delay ); ?>"
					   min="1" max="720" step="1" />
				<p class="description"><?php esc_html_e( 'Hours between each scheduled post when using the "Scheduled" post status.', 'inkbridge-gen' ); ?></p>
			</td>
		</tr>

		<!-- Translation Meta Key -->
		<tr>
			<th scope="row">
				<label for="inkbridge-gen-translation-meta"><?php esc_html_e( 'Translation Meta Key', 'inkbridge-gen' ); ?></label>
			</th>
			<td>
				<input type="text" id="inkbridge-gen-translation-meta" name="translation_meta_key"
					   class="regular-text" value="<?php echo esc_attr( $translation_meta ); ?>" />
				<p class="description"><?php esc_html_e( 'Post meta key used to store the relationship between translated posts. Change only if it conflicts with another plugin.', 'inkbridge-gen' ); ?></p>
			</td>
		</tr>
	</table>

	<p class="submit">
		<button type="button" class="inkbridge-gen-save-settings button button-primary" data-section="general">
			<?php esc_html_e( 'Save Settings', 'inkbridge-gen' ); ?>
		</button>
	</p>

</form>
