<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<?php if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Permission denied.', 'inkbridge-gen' ) ); } ?>
<?php
$settings = new Inkbridge_Gen_Settings();
$db       = new Inkbridge_Gen_DB();

// Stats.
$queue_counts   = $db->get_queue_counts();
$month_stats    = $db->get_log_stats( '30d' );
$today_stats    = $db->get_log_stats( 'today' );
$articles_count = $queue_counts['completed'];
$queue_pending  = $queue_counts['pending'];
$tokens_month   = absint( $month_stats->total_input_tokens ?? 0 ) + absint( $month_stats->total_output_tokens ?? 0 );
$api_calls_today = absint( $today_stats->total_calls ?? 0 );
$errors_today    = absint( $today_stats->error_count ?? 0 );

// Recent generations.
$recent_logs = $db->get_logs( array(
	'type'     => 'generate_article',
	'per_page' => 10,
) );

// Provider info.
$active_text  = $settings->get_active_text_provider();
$active_image = $settings->get_active_image_provider();
$text_config  = $settings->get_text_provider_config();

$pillars = $settings->get_pillars();
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Inkbridge Generator', 'inkbridge-gen' ); ?></h1>

	<!-- Stats Cards -->
	<div class="inkbridge-gen-stats-row">
		<div class="inkbridge-gen-stat-card">
			<h3><?php esc_html_e( 'Articles Generated', 'inkbridge-gen' ); ?></h3>
			<span class="inkbridge-gen-stat-number"><?php echo esc_html( number_format_i18n( $articles_count ) ); ?></span>
		</div>
		<div class="inkbridge-gen-stat-card">
			<h3><?php esc_html_e( 'Queue Pending', 'inkbridge-gen' ); ?></h3>
			<span class="inkbridge-gen-stat-number"><?php echo esc_html( number_format_i18n( $queue_pending ) ); ?></span>
		</div>
		<div class="inkbridge-gen-stat-card">
			<h3><?php esc_html_e( 'Tokens Used This Month', 'inkbridge-gen' ); ?></h3>
			<span class="inkbridge-gen-stat-number"><?php echo esc_html( number_format_i18n( $tokens_month ) ); ?></span>
		</div>
		<div class="inkbridge-gen-stat-card">
			<h3><?php esc_html_e( 'API Calls Today', 'inkbridge-gen' ); ?></h3>
			<span class="inkbridge-gen-stat-number"><?php echo esc_html( number_format_i18n( $api_calls_today ) ); ?></span>
		</div>
		<div class="inkbridge-gen-stat-card">
			<h3><?php esc_html_e( 'Errors', 'inkbridge-gen' ); ?></h3>
			<span class="inkbridge-gen-stat-number"><?php echo esc_html( number_format_i18n( $errors_today ) ); ?></span>
		</div>
	</div>

	<!-- Two-Column Layout -->
	<div class="inkbridge-gen-columns">

		<!-- Left: Recent Generations -->
		<div class="inkbridge-gen-column-left">
			<div class="postbox">
				<h2 class="hndle"><span><?php esc_html_e( 'Recent Generations', 'inkbridge-gen' ); ?></span></h2>
				<div class="inside">
					<?php if ( ! empty( $recent_logs ) ) : ?>
						<table class="widefat striped">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Topic', 'inkbridge-gen' ); ?></th>
									<th><?php esc_html_e( 'Language', 'inkbridge-gen' ); ?></th>
									<th><?php esc_html_e( 'Status', 'inkbridge-gen' ); ?></th>
									<th><?php esc_html_e( 'Date', 'inkbridge-gen' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $recent_logs as $log ) : ?>
									<tr>
										<td><?php echo esc_html( $log->topic ); ?></td>
										<td><?php echo esc_html( $log->language ); ?></td>
										<td>
											<span class="inkbridge-gen-badge inkbridge-gen-badge-<?php echo esc_attr( $log->status ); ?>">
												<?php echo esc_html( ucfirst( $log->status ) ); ?>
											</span>
										</td>
										<td><?php echo esc_html( $log->created_at ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php else : ?>
						<p><?php esc_html_e( 'No recent generations found.', 'inkbridge-gen' ); ?></p>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<!-- Right: Quick Generate -->
		<div class="inkbridge-gen-column-right">
			<div class="postbox">
				<h2 class="hndle"><span><?php esc_html_e( 'Quick Generate', 'inkbridge-gen' ); ?></span></h2>
				<div class="inside">
					<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
						<input type="hidden" name="page" value="inkbridge-gen-generate" />
						<table class="form-table">
							<tr>
								<th scope="row">
									<label for="inkbridge-gen-quick-topic"><?php esc_html_e( 'Topic', 'inkbridge-gen' ); ?></label>
								</th>
								<td>
									<div style="display: flex; gap: 6px; align-items: center;">
										<input type="text" id="inkbridge-gen-quick-topic" name="topic" class="regular-text" required />
										<button type="button" class="button inkbridge-gen-suggest-topic-btn" data-target="#inkbridge-gen-quick-topic" data-pillar="#inkbridge-gen-quick-pillar">
											<span class="dashicons dashicons-lightbulb" style="vertical-align: middle; margin-top: -2px;"></span>
											<span class="inkbridge-gen-suggest-label"><?php esc_html_e( 'Suggest', 'inkbridge-gen' ); ?></span>
										</button>
									</div>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="inkbridge-gen-quick-pillar"><?php esc_html_e( 'Content Pillar', 'inkbridge-gen' ); ?></label>
								</th>
								<td>
									<select id="inkbridge-gen-quick-pillar" name="pillar">
										<option value=""><?php esc_html_e( '-- Select Pillar --', 'inkbridge-gen' ); ?></option>
										<?php foreach ( $pillars as $pillar ) : ?>
											<option value="<?php echo esc_attr( $pillar['key'] ); ?>">
												<?php echo esc_html( $pillar['label'] ); ?>
											</option>
										<?php endforeach; ?>
									</select>
								</td>
							</tr>
						</table>
						<p>
							<button type="submit" class="button button-primary">
								<?php esc_html_e( 'Generate Article', 'inkbridge-gen' ); ?>
							</button>
						</p>
					</form>
				</div>
			</div>
		</div>

	</div>

	<!-- Provider Status -->
	<div class="postbox">
		<h2 class="hndle"><span><?php esc_html_e( 'Provider Status', 'inkbridge-gen' ); ?></span></h2>
		<div class="inside">
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Type', 'inkbridge-gen' ); ?></th>
						<th><?php esc_html_e( 'Provider', 'inkbridge-gen' ); ?></th>
						<th><?php esc_html_e( 'Model', 'inkbridge-gen' ); ?></th>
						<th><?php esc_html_e( 'Status', 'inkbridge-gen' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><?php esc_html_e( 'Text', 'inkbridge-gen' ); ?></td>
						<td><?php echo esc_html( ucfirst( $active_text ) ); ?></td>
						<td><?php echo esc_html( $text_config['model'] ?: __( 'Not set', 'inkbridge-gen' ) ); ?></td>
						<td>
							<?php if ( $settings->has_provider_api_key( $active_text ) ) : ?>
								<span class="inkbridge-gen-badge inkbridge-gen-badge-success"><?php esc_html_e( 'API Key Set', 'inkbridge-gen' ); ?></span>
							<?php else : ?>
								<span class="inkbridge-gen-badge inkbridge-gen-badge-error"><?php esc_html_e( 'No API Key', 'inkbridge-gen' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Image', 'inkbridge-gen' ); ?></td>
						<td><?php echo esc_html( ucfirst( $active_image ) ); ?></td>
						<td>&#8212;</td>
						<td>
							<?php if ( $settings->has_provider_api_key( $active_image ) ) : ?>
								<span class="inkbridge-gen-badge inkbridge-gen-badge-success"><?php esc_html_e( 'API Key Set', 'inkbridge-gen' ); ?></span>
							<?php else : ?>
								<span class="inkbridge-gen-badge inkbridge-gen-badge-error"><?php esc_html_e( 'No API Key', 'inkbridge-gen' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>

</div>
