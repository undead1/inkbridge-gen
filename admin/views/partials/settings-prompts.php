<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<?php
$prompt_generate_system  = $settings->get_prompt( 'generate_system' );
$prompt_generate_user    = $settings->get_prompt( 'generate_user' );
$prompt_translate_system = $settings->get_prompt( 'translate_system' );
$prompt_translate_user   = $settings->get_prompt( 'translate_user' );
?>
<form class="inkbridge-gen-settings-form" data-section="prompts">
	<?php wp_nonce_field( 'inkbridge_gen_admin', '_inkbridge_gen_nonce' ); ?>

	<table class="form-table">

		<!-- Generation System Prompt -->
		<tr>
			<th scope="row">
				<label for="inkbridge-gen-prompt-gen-system"><?php esc_html_e( 'Generation System Prompt', 'inkbridge-gen' ); ?></label>
			</th>
			<td>
				<textarea id="inkbridge-gen-prompt-gen-system" name="prompt_generate_system"
						  class="large-text" rows="10"><?php echo esc_textarea( $prompt_generate_system ); ?></textarea>
				<p class="description">
					<?php esc_html_e( 'Available placeholders: {language}, {word_count}, {pillar_label}, {pillar_context}', 'inkbridge-gen' ); ?>
				</p>
			</td>
		</tr>

		<!-- Generation User Prompt -->
		<tr>
			<th scope="row">
				<label for="inkbridge-gen-prompt-gen-user"><?php esc_html_e( 'Generation User Prompt', 'inkbridge-gen' ); ?></label>
			</th>
			<td>
				<textarea id="inkbridge-gen-prompt-gen-user" name="prompt_generate_user"
						  class="large-text" rows="12"><?php echo esc_textarea( $prompt_generate_user ); ?></textarea>
				<p class="description">
					<?php esc_html_e( 'Available placeholders: {topic}, {language}, {word_count}, {pillar_label}, {pillar_context}, {extra_context}', 'inkbridge-gen' ); ?>
				</p>
			</td>
		</tr>

		<!-- Translation System Prompt -->
		<tr>
			<th scope="row">
				<label for="inkbridge-gen-prompt-trans-system"><?php esc_html_e( 'Translation System Prompt', 'inkbridge-gen' ); ?></label>
			</th>
			<td>
				<textarea id="inkbridge-gen-prompt-trans-system" name="prompt_translate_system"
						  class="large-text" rows="8"><?php echo esc_textarea( $prompt_translate_system ); ?></textarea>
				<p class="description">
					<?php esc_html_e( 'Available placeholders: {source_language}, {target_language}', 'inkbridge-gen' ); ?>
				</p>
			</td>
		</tr>

		<!-- Translation User Prompt -->
		<tr>
			<th scope="row">
				<label for="inkbridge-gen-prompt-trans-user"><?php esc_html_e( 'Translation User Prompt', 'inkbridge-gen' ); ?></label>
			</th>
			<td>
				<textarea id="inkbridge-gen-prompt-trans-user" name="prompt_translate_user"
						  class="large-text" rows="12"><?php echo esc_textarea( $prompt_translate_user ); ?></textarea>
				<p class="description">
					<?php esc_html_e( 'Available placeholders: {source_language}, {target_language}, {source_title}, {source_content}, {pillar_label}, {pillar_context}', 'inkbridge-gen' ); ?>
				</p>
			</td>
		</tr>

	</table>

	<p class="submit">
		<button type="button" id="inkbridge-gen-reset-prompts" class="button button-secondary">
			<?php esc_html_e( 'Reset to Defaults', 'inkbridge-gen' ); ?>
		</button>
		<button type="button" class="inkbridge-gen-save-settings button button-primary" data-section="prompts">
			<?php esc_html_e( 'Save Settings', 'inkbridge-gen' ); ?>
		</button>
	</p>

</form>
