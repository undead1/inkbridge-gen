<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<?php
$pillars   = $settings->get_pillars();
$languages = $settings->get_languages();
?>
<form class="inkbridge-gen-settings-form" data-section="pillars">
	<?php wp_nonce_field( 'inkbridge_gen_admin', '_inkbridge_gen_nonce' ); ?>

	<p class="description">
		<?php esc_html_e( 'Category slugs must match existing WordPress category slugs. Each pillar maps to a category per language.', 'inkbridge-gen' ); ?>
	</p>

	<table class="widefat striped inkbridge-gen-pillars-table" id="inkbridge-gen-pillars-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Key', 'inkbridge-gen' ); ?></th>
				<th><?php esc_html_e( 'Label', 'inkbridge-gen' ); ?></th>
				<?php foreach ( $languages as $lang ) : ?>
					<th>
						<?php
						printf(
							/* translators: %s: language name */
							esc_html__( 'Category (%s)', 'inkbridge-gen' ),
							esc_html( $lang['name'] )
						);
						?>
					</th>
				<?php endforeach; ?>
				<th><?php esc_html_e( 'Context', 'inkbridge-gen' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'inkbridge-gen' ); ?></th>
			</tr>
		</thead>
		<tbody id="inkbridge-gen-pillars-body">
			<?php if ( ! empty( $pillars ) ) : ?>
				<?php foreach ( $pillars as $index => $pillar ) : ?>
					<tr data-index="<?php echo esc_attr( $index ); ?>">
						<td>
							<input type="text" name="pillars[<?php echo esc_attr( $index ); ?>][key]"
								   class="small-text" value="<?php echo esc_attr( $pillar['key'] ); ?>"
								   placeholder="pillar-key" />
						</td>
						<td>
							<input type="text" name="pillars[<?php echo esc_attr( $index ); ?>][label]"
								   class="regular-text" value="<?php echo esc_attr( $pillar['label'] ); ?>"
								   placeholder="Pillar Label" />
						</td>
						<?php foreach ( $languages as $lang ) : ?>
							<td>
								<input type="text"
									   name="pillars[<?php echo esc_attr( $index ); ?>][categories][<?php echo esc_attr( $lang['code'] ); ?>]"
									   class="small-text"
									   value="<?php echo esc_attr( $pillar['categories'][ $lang['code'] ] ?? '' ); ?>"
									   placeholder="<?php echo esc_attr( $lang['code'] ); ?>-slug" />
							</td>
						<?php endforeach; ?>
						<td>
							<textarea name="pillars[<?php echo esc_attr( $index ); ?>][context]"
									  rows="2" class="large-text"><?php echo esc_textarea( $pillar['context'] ?? '' ); ?></textarea>
						</td>
						<td>
							<button type="button" class="button button-small inkbridge-gen-remove-pillar">
								<?php esc_html_e( 'Remove', 'inkbridge-gen' ); ?>
							</button>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>

	<p>
		<button type="button" id="inkbridge-gen-add-pillar" class="button button-secondary">
			<?php esc_html_e( 'Add Pillar', 'inkbridge-gen' ); ?>
		</button>
		<button type="button" id="inkbridge-gen-generate-pillars" class="button button-secondary" style="margin-left: 8px;">
			<?php esc_html_e( 'Generate from Categories', 'inkbridge-gen' ); ?>
		</button>
		<span id="inkbridge-gen-generate-pillars-status" style="margin-left: 8px;"></span>
	</p>

	<!-- Template row for JS (hidden) -->
	<script type="text/html" id="tmpl-inkbridge-gen-pillar-row">
		<tr data-index="{{data.index}}">
			<td>
				<input type="text" name="pillars[{{data.index}}][key]"
					   class="small-text" placeholder="pillar-key" />
			</td>
			<td>
				<input type="text" name="pillars[{{data.index}}][label]"
					   class="regular-text" placeholder="Pillar Label" />
			</td>
			<?php foreach ( $languages as $lang ) : ?>
				<td>
					<input type="text"
						   name="pillars[{{data.index}}][categories][<?php echo esc_attr( $lang['code'] ); ?>]"
						   class="small-text"
						   placeholder="<?php echo esc_attr( $lang['code'] ); ?>-slug" />
				</td>
			<?php endforeach; ?>
			<td>
				<textarea name="pillars[{{data.index}}][context]"
						  rows="2" class="large-text"></textarea>
			</td>
			<td>
				<button type="button" class="button button-small inkbridge-gen-remove-pillar">
					<?php esc_html_e( 'Remove', 'inkbridge-gen' ); ?>
				</button>
			</td>
		</tr>
	</script>

	<p class="submit">
		<button type="button" class="inkbridge-gen-save-settings button button-primary" data-section="pillars">
			<?php esc_html_e( 'Save Settings', 'inkbridge-gen' ); ?>
		</button>
	</p>

</form>
