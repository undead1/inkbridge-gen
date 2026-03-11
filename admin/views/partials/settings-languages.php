<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<?php
$languages = $settings->get_languages();
?>
<form class="inkbridge-gen-settings-form" data-section="languages">
	<?php wp_nonce_field( 'inkbridge_gen_admin', '_inkbridge_gen_nonce' ); ?>

	<p class="description">
		<?php esc_html_e( 'Source language is used for initial article generation. Others are translation targets.', 'inkbridge-gen' ); ?>
	</p>

	<table class="widefat striped inkbridge-gen-languages-table" id="inkbridge-gen-languages-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Code', 'inkbridge-gen' ); ?></th>
				<th><?php esc_html_e( 'Name', 'inkbridge-gen' ); ?></th>
				<th><?php esc_html_e( 'Hreflang', 'inkbridge-gen' ); ?></th>
				<th><?php esc_html_e( 'Parent Category Slug', 'inkbridge-gen' ); ?></th>
				<th><?php esc_html_e( 'Source Language', 'inkbridge-gen' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'inkbridge-gen' ); ?></th>
			</tr>
		</thead>
		<tbody id="inkbridge-gen-languages-body">
			<?php if ( ! empty( $languages ) ) : ?>
				<?php foreach ( $languages as $index => $lang ) : ?>
					<tr data-index="<?php echo esc_attr( $index ); ?>">
						<td>
							<input type="text" name="languages[<?php echo esc_attr( $index ); ?>][code]"
								   class="small-text" value="<?php echo esc_attr( $lang['code'] ); ?>"
								   placeholder="en" />
						</td>
						<td>
							<input type="text" name="languages[<?php echo esc_attr( $index ); ?>][name]"
								   class="regular-text" value="<?php echo esc_attr( $lang['name'] ); ?>"
								   placeholder="English" />
						</td>
						<td>
							<input type="text" name="languages[<?php echo esc_attr( $index ); ?>][hreflang]"
								   class="small-text" value="<?php echo esc_attr( $lang['hreflang'] ); ?>"
								   placeholder="en-US" />
						</td>
						<td>
							<input type="text" name="languages[<?php echo esc_attr( $index ); ?>][parent_category]"
								   class="regular-text" value="<?php echo esc_attr( $lang['parent_category'] ); ?>"
								   placeholder="english" />
						</td>
						<td>
							<input type="checkbox" name="languages[<?php echo esc_attr( $index ); ?>][is_source]"
								   value="1" <?php checked( ! empty( $lang['is_source'] ) ); ?>
								   class="inkbridge-gen-source-checkbox" />
						</td>
						<td>
							<button type="button" class="button button-small inkbridge-gen-remove-language">
								<?php esc_html_e( 'Remove', 'inkbridge-gen' ); ?>
							</button>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>

	<p>
		<button type="button" id="inkbridge-gen-add-language" class="button button-secondary">
			<?php esc_html_e( 'Add Language', 'inkbridge-gen' ); ?>
		</button>
	</p>

	<!-- Template row for JS (hidden) -->
	<script type="text/html" id="tmpl-inkbridge-gen-language-row">
		<tr data-index="{{data.index}}">
			<td>
				<input type="text" name="languages[{{data.index}}][code]"
					   class="small-text" placeholder="en" />
			</td>
			<td>
				<input type="text" name="languages[{{data.index}}][name]"
					   class="regular-text" placeholder="English" />
			</td>
			<td>
				<input type="text" name="languages[{{data.index}}][hreflang]"
					   class="small-text" placeholder="en-US" />
			</td>
			<td>
				<input type="text" name="languages[{{data.index}}][parent_category]"
					   class="regular-text" placeholder="english" />
			</td>
			<td>
				<input type="checkbox" name="languages[{{data.index}}][is_source]"
					   value="1" class="inkbridge-gen-source-checkbox" />
			</td>
			<td>
				<button type="button" class="button button-small inkbridge-gen-remove-language">
					<?php esc_html_e( 'Remove', 'inkbridge-gen' ); ?>
				</button>
			</td>
		</tr>
	</script>

	<p class="submit">
		<button type="button" class="inkbridge-gen-save-settings button button-primary" data-section="languages">
			<?php esc_html_e( 'Save Settings', 'inkbridge-gen' ); ?>
		</button>
	</p>

</form>
