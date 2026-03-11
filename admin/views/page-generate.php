<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<?php if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Permission denied.', 'inkbridge-gen' ) ); } ?>
<?php
$settings   = new Inkbridge_Gen_Settings();
$pillars    = $settings->get_pillars();
$languages  = $settings->get_languages();
$source     = $settings->get_source_language();
$word_count = $settings->get( 'default_word_count', 1500 );

// Pre-fill from query params (Quick Generate).
$prefill_topic  = sanitize_text_field( $_GET['topic'] ?? '' );
$prefill_pillar = sanitize_text_field( $_GET['pillar'] ?? '' );

// Text provider models map.
$text_models = array(
	'openai' => array( 'gpt-4o-mini', 'gpt-4o', 'gpt-4.1-nano', 'gpt-4.1-mini', 'gpt-4.1' ),
	'claude' => array( 'claude-sonnet-4-20250514', 'claude-haiku-4-5-20251001' ),
	'gemini' => array( 'gemini-2.0-flash', 'gemini-2.5-pro', 'gemini-2.5-flash' ),
);

$text_providers  = array( 'openai' => 'OpenAI', 'claude' => 'Claude', 'gemini' => 'Gemini' );
$image_providers = array( 'unsplash' => 'Unsplash', 'shutterstock' => 'Shutterstock', 'depositphotos' => 'DepositPhotos' );
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Generate Article', 'inkbridge-gen' ); ?></h1>

	<form id="inkbridge-gen-generate-form" class="inkbridge-gen-generate-form" method="post">

		<table class="form-table">
			<!-- Topic -->
			<tr>
				<th scope="row">
					<label for="inkbridge-gen-topic"><?php esc_html_e( 'Topic', 'inkbridge-gen' ); ?> <span class="required">*</span></label>
				</th>
				<td>
					<div style="display: flex; gap: 6px; align-items: center;">
						<input type="text" id="inkbridge-gen-topic" name="topic" class="large-text" required
							   value="<?php echo esc_attr( $prefill_topic ); ?>" />
						<button type="button" class="button inkbridge-gen-suggest-topic-btn" data-target="#inkbridge-gen-topic" data-pillar="#inkbridge-gen-pillar" style="white-space: nowrap;">
							<span class="dashicons dashicons-lightbulb" style="vertical-align: middle; margin-top: -2px;"></span>
							<span class="inkbridge-gen-suggest-label"><?php esc_html_e( 'Suggest', 'inkbridge-gen' ); ?></span>
						</button>
					</div>
				</td>
			</tr>

			<!-- Pillar -->
			<tr>
				<th scope="row">
					<label for="inkbridge-gen-pillar"><?php esc_html_e( 'Content Pillar', 'inkbridge-gen' ); ?></label>
				</th>
				<td>
					<select id="inkbridge-gen-pillar" name="pillar">
						<option value=""><?php esc_html_e( '-- Select Pillar --', 'inkbridge-gen' ); ?></option>
						<?php foreach ( $pillars as $pillar ) : ?>
							<option value="<?php echo esc_attr( $pillar['key'] ); ?>" <?php selected( $prefill_pillar, $pillar['key'] ); ?>>
								<?php echo esc_html( $pillar['label'] ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>

			<!-- Languages -->
			<tr>
				<th scope="row"><?php esc_html_e( 'Languages', 'inkbridge-gen' ); ?></th>
				<td>
					<fieldset>
						<?php foreach ( $languages as $lang ) : ?>
							<label style="display: inline-block; margin-right: 15px;">
								<input type="checkbox" name="languages[]" value="<?php echo esc_attr( $lang['code'] ); ?>"
									<?php checked( true ); ?>
									<?php if ( ! empty( $lang['is_source'] ) ) : ?>
										disabled
									<?php endif; ?>
								/>
								<?php echo esc_html( $lang['name'] ); ?>
								<?php if ( ! empty( $lang['is_source'] ) ) : ?>
									<input type="hidden" name="languages[]" value="<?php echo esc_attr( $lang['code'] ); ?>" />
									<em>(<?php esc_html_e( 'source', 'inkbridge-gen' ); ?>)</em>
								<?php endif; ?>
							</label>
						<?php endforeach; ?>
					</fieldset>
				</td>
			</tr>

			<!-- Word Count -->
			<tr>
				<th scope="row">
					<label for="inkbridge-gen-word-count"><?php esc_html_e( 'Word Count', 'inkbridge-gen' ); ?></label>
				</th>
				<td>
					<input type="number" id="inkbridge-gen-word-count" name="word_count" class="small-text"
						   value="<?php echo esc_attr( $word_count ); ?>" min="100" max="10000" step="100" />
				</td>
			</tr>

			<!-- Extra Context -->
			<tr>
				<th scope="row">
					<label for="inkbridge-gen-extra-context"><?php esc_html_e( 'Extra Context', 'inkbridge-gen' ); ?></label>
				</th>
				<td>
					<textarea id="inkbridge-gen-extra-context" name="extra_context" class="large-text" rows="4"></textarea>
					<p class="description"><?php esc_html_e( 'Optional additional context or instructions for the AI.', 'inkbridge-gen' ); ?></p>
				</td>
			</tr>

			<!-- Post Status -->
			<tr>
				<th scope="row"><?php esc_html_e( 'Post Status', 'inkbridge-gen' ); ?></th>
				<td>
					<fieldset>
						<label>
							<input type="radio" name="post_status" value="draft" checked />
							<?php esc_html_e( 'Draft', 'inkbridge-gen' ); ?>
						</label><br />
						<label>
							<input type="radio" name="post_status" value="publish" />
							<?php esc_html_e( 'Publish', 'inkbridge-gen' ); ?>
						</label><br />
						<label>
							<input type="radio" name="post_status" value="future" />
							<?php esc_html_e( 'Scheduled', 'inkbridge-gen' ); ?>
						</label>
					</fieldset>
				</td>
			</tr>
		</table>

		<!-- Advanced Options (collapsible) -->
		<div class="postbox inkbridge-gen-advanced-options">
			<button type="button" class="handlediv" aria-expanded="false">
				<span class="toggle-indicator" aria-hidden="true"></span>
			</button>
			<h2 class="hndle"><span><?php esc_html_e( 'Advanced Options', 'inkbridge-gen' ); ?></span></h2>
			<div class="inside" style="display: none;">
				<table class="form-table">
					<!-- Text Provider Override -->
					<tr>
						<th scope="row">
							<label for="inkbridge-gen-text-provider-override"><?php esc_html_e( 'Text Provider Override', 'inkbridge-gen' ); ?></label>
						</th>
						<td>
							<select id="inkbridge-gen-text-provider-override" name="text_provider_override">
								<option value=""><?php esc_html_e( '-- Use Default --', 'inkbridge-gen' ); ?></option>
								<?php foreach ( $text_providers as $pid => $label ) : ?>
									<option value="<?php echo esc_attr( $pid ); ?>"><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>

					<!-- Model Override -->
					<tr>
						<th scope="row">
							<label for="inkbridge-gen-model-override"><?php esc_html_e( 'Model Override', 'inkbridge-gen' ); ?></label>
						</th>
						<td>
							<select id="inkbridge-gen-model-override" name="model_override">
								<option value=""><?php esc_html_e( '-- Use Default --', 'inkbridge-gen' ); ?></option>
								<?php foreach ( $text_models as $provider => $models ) : ?>
									<optgroup label="<?php echo esc_attr( $text_providers[ $provider ] ); ?>">
										<?php foreach ( $models as $model ) : ?>
											<option value="<?php echo esc_attr( $model ); ?>"><?php echo esc_html( $model ); ?></option>
										<?php endforeach; ?>
									</optgroup>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>

					<!-- Image Provider Override -->
					<tr>
						<th scope="row">
							<label for="inkbridge-gen-image-provider-override"><?php esc_html_e( 'Image Provider Override', 'inkbridge-gen' ); ?></label>
						</th>
						<td>
							<select id="inkbridge-gen-image-provider-override" name="image_provider_override">
								<option value=""><?php esc_html_e( '-- Use Default --', 'inkbridge-gen' ); ?></option>
								<?php foreach ( $image_providers as $pid => $label ) : ?>
									<option value="<?php echo esc_attr( $pid ); ?>"><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>

					<!-- Skip Image -->
					<tr>
						<th scope="row"><?php esc_html_e( 'Skip Image', 'inkbridge-gen' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="skip_image" value="1" />
								<?php esc_html_e( 'Do not fetch a featured image for this article', 'inkbridge-gen' ); ?>
							</label>
						</td>
					</tr>
				</table>
			</div>
		</div>

		<!-- Action Buttons -->
		<p class="submit">
			<button type="button" id="inkbridge-gen-generate-btn" class="button button-primary button-hero">
				<?php esc_html_e( 'Generate Article', 'inkbridge-gen' ); ?>
			</button>
			<button type="button" id="inkbridge-gen-queue-btn" class="button button-secondary button-hero">
				<?php esc_html_e( 'Add to Queue Instead', 'inkbridge-gen' ); ?>
			</button>
		</p>

	</form>

	<!-- Progress Section (hidden by default) -->
	<div id="inkbridge-gen-progress" class="postbox" style="display: none;">
		<h2 class="hndle"><span><?php esc_html_e( 'Generation Progress', 'inkbridge-gen' ); ?></span></h2>
		<div class="inside">
			<ul id="inkbridge-gen-progress-steps" class="inkbridge-gen-steps-list">
				<!-- Steps will be populated by JS. Example structure:
				<li data-step="1">
					<span class="inkbridge-gen-step-icon dashicons dashicons-update spin"></span>
					<span class="inkbridge-gen-step-label">Generating article...</span>
				</li>
				-->
			</ul>
		</div>
	</div>

	<!-- Results Section (hidden by default) -->
	<div id="inkbridge-gen-results" class="postbox" style="display: none;">
		<h2 class="hndle"><span><?php esc_html_e( 'Generated Posts', 'inkbridge-gen' ); ?></span></h2>
		<div class="inside">
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Title', 'inkbridge-gen' ); ?></th>
						<th><?php esc_html_e( 'Language', 'inkbridge-gen' ); ?></th>
						<th><?php esc_html_e( 'Status', 'inkbridge-gen' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'inkbridge-gen' ); ?></th>
					</tr>
				</thead>
				<tbody id="inkbridge-gen-results-body">
					<!-- Rows will be populated by JS. Example structure:
					<tr>
						<td>Article Title</td>
						<td>English</td>
						<td><span class="inkbridge-gen-badge inkbridge-gen-badge-success">Draft</span></td>
						<td>
							<a href="#" class="button button-small" target="_blank">View</a>
							<a href="#" class="button button-small">Edit</a>
						</td>
					</tr>
					-->
				</tbody>
			</table>
		</div>
	</div>

</div>
