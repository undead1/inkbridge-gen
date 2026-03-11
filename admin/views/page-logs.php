<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<?php if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Permission denied.', 'inkbridge-gen' ) ); } ?>
<?php
$settings = new Inkbridge_Gen_Settings();
$db       = new Inkbridge_Gen_DB();

// Filter params.
$current_range    = sanitize_text_field( $_GET['range'] ?? '30d' );
$current_provider = sanitize_text_field( $_GET['provider'] ?? '' );
$current_status   = sanitize_text_field( $_GET['status'] ?? '' );
$current_page_num = max( 1, absint( $_GET['paged'] ?? 1 ) );
$per_page         = 20;

$filter_args = array(
	'per_page' => $per_page,
	'offset'   => ( $current_page_num - 1 ) * $per_page,
);

if ( $current_range && 'all' !== $current_range ) {
	$filter_args['range'] = $current_range;
}
if ( $current_provider ) {
	$filter_args['provider'] = $current_provider;
}
if ( $current_status ) {
	$filter_args['status'] = $current_status;
}

$logs       = $db->get_logs( $filter_args );
$total_logs = $db->get_log_count( $filter_args );
$total_pages = ceil( $total_logs / $per_page );

// Stats for cards.
$stats = $db->get_log_stats( $current_range && 'all' !== $current_range ? $current_range : '30d' );

$base_url = admin_url( 'admin.php?page=inkbridge-gen-logs' );
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Logs', 'inkbridge-gen' ); ?></h1>

	<!-- Filter Row -->
	<div class="inkbridge-gen-filter-row">
		<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
			<input type="hidden" name="page" value="inkbridge-gen-logs" />

			<!-- Date Range Buttons -->
			<span class="inkbridge-gen-filter-group">
				<strong><?php esc_html_e( 'Range:', 'inkbridge-gen' ); ?></strong>
				<?php
				$ranges = array(
					'today' => __( 'Today', 'inkbridge-gen' ),
					'7d'    => __( '7d', 'inkbridge-gen' ),
					'30d'   => __( '30d', 'inkbridge-gen' ),
					'90d'   => __( '90d', 'inkbridge-gen' ),
					'all'   => __( 'All', 'inkbridge-gen' ),
				);
				foreach ( $ranges as $key => $label ) : ?>
					<a href="<?php echo esc_url( add_query_arg( array( 'range' => $key, 'provider' => $current_provider, 'status' => $current_status, 'paged' => 1 ), $base_url ) ); ?>"
					   class="button <?php echo $current_range === $key ? 'button-primary' : 'button-secondary'; ?>">
						<?php echo esc_html( $label ); ?>
					</a>
				<?php endforeach; ?>
			</span>

			<!-- Provider Dropdown -->
			<span class="inkbridge-gen-filter-group">
				<label for="inkbridge-gen-filter-provider"><?php esc_html_e( 'Provider:', 'inkbridge-gen' ); ?></label>
				<select id="inkbridge-gen-filter-provider" name="provider">
					<option value=""><?php esc_html_e( 'All', 'inkbridge-gen' ); ?></option>
					<?php foreach ( array( 'openai', 'claude', 'gemini', 'unsplash', 'shutterstock', 'depositphotos' ) as $p ) : ?>
						<option value="<?php echo esc_attr( $p ); ?>" <?php selected( $current_provider, $p ); ?>>
							<?php echo esc_html( ucfirst( $p ) ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</span>

			<!-- Status Dropdown -->
			<span class="inkbridge-gen-filter-group">
				<label for="inkbridge-gen-filter-status"><?php esc_html_e( 'Status:', 'inkbridge-gen' ); ?></label>
				<select id="inkbridge-gen-filter-status" name="status">
					<option value=""><?php esc_html_e( 'All', 'inkbridge-gen' ); ?></option>
					<option value="success" <?php selected( $current_status, 'success' ); ?>><?php esc_html_e( 'Success', 'inkbridge-gen' ); ?></option>
					<option value="error" <?php selected( $current_status, 'error' ); ?>><?php esc_html_e( 'Error', 'inkbridge-gen' ); ?></option>
				</select>
			</span>

			<input type="hidden" name="range" value="<?php echo esc_attr( $current_range ); ?>" />
			<button type="submit" class="button button-secondary"><?php esc_html_e( 'Filter', 'inkbridge-gen' ); ?></button>
		</form>
	</div>

	<!-- Stats Cards -->
	<div class="inkbridge-gen-stats-row">
		<div class="inkbridge-gen-stat-card">
			<h3><?php esc_html_e( 'Total Calls', 'inkbridge-gen' ); ?></h3>
			<span class="inkbridge-gen-stat-number"><?php echo esc_html( number_format_i18n( $stats->total_calls ?? 0 ) ); ?></span>
		</div>
		<div class="inkbridge-gen-stat-card">
			<h3><?php esc_html_e( 'Input Tokens', 'inkbridge-gen' ); ?></h3>
			<span class="inkbridge-gen-stat-number"><?php echo esc_html( number_format_i18n( $stats->total_input_tokens ?? 0 ) ); ?></span>
		</div>
		<div class="inkbridge-gen-stat-card">
			<h3><?php esc_html_e( 'Output Tokens', 'inkbridge-gen' ); ?></h3>
			<span class="inkbridge-gen-stat-number"><?php echo esc_html( number_format_i18n( $stats->total_output_tokens ?? 0 ) ); ?></span>
		</div>
		<div class="inkbridge-gen-stat-card">
			<h3><?php esc_html_e( 'Errors', 'inkbridge-gen' ); ?></h3>
			<span class="inkbridge-gen-stat-number"><?php echo esc_html( number_format_i18n( $stats->error_count ?? 0 ) ); ?></span>
		</div>
		<div class="inkbridge-gen-stat-card">
			<h3><?php esc_html_e( 'Avg Duration', 'inkbridge-gen' ); ?></h3>
			<span class="inkbridge-gen-stat-number"><?php echo esc_html( number_format( $stats->avg_duration_ms ?? 0, 0 ) ); ?> ms</span>
		</div>
	</div>

	<!-- Logs Table -->
	<table class="widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Timestamp', 'inkbridge-gen' ); ?></th>
				<th><?php esc_html_e( 'Type', 'inkbridge-gen' ); ?></th>
				<th><?php esc_html_e( 'Provider', 'inkbridge-gen' ); ?></th>
				<th><?php esc_html_e( 'Model', 'inkbridge-gen' ); ?></th>
				<th><?php esc_html_e( 'Topic', 'inkbridge-gen' ); ?></th>
				<th><?php esc_html_e( 'Language', 'inkbridge-gen' ); ?></th>
				<th><?php esc_html_e( 'Input Tokens', 'inkbridge-gen' ); ?></th>
				<th><?php esc_html_e( 'Output Tokens', 'inkbridge-gen' ); ?></th>
				<th><?php esc_html_e( 'Duration (ms)', 'inkbridge-gen' ); ?></th>
				<th><?php esc_html_e( 'Status', 'inkbridge-gen' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( ! empty( $logs ) ) : ?>
				<?php foreach ( $logs as $log ) : ?>
					<tr>
						<td><?php echo esc_html( $log->created_at ); ?></td>
						<td><?php echo esc_html( $log->type ); ?></td>
						<td><?php echo esc_html( ucfirst( $log->provider ) ); ?></td>
						<td><?php echo esc_html( $log->model ?: '&#8212;' ); ?></td>
						<td><?php echo esc_html( $log->topic ); ?></td>
						<td><?php echo esc_html( $log->language ?: '&#8212;' ); ?></td>
						<td><?php echo esc_html( number_format_i18n( $log->prompt_tokens ) ); ?></td>
						<td><?php echo esc_html( number_format_i18n( $log->completion_tokens ) ); ?></td>
						<td><?php echo esc_html( number_format_i18n( $log->duration_ms ) ); ?></td>
						<td>
							<span class="inkbridge-gen-badge inkbridge-gen-badge-<?php echo esc_attr( $log->status ); ?>">
								<?php echo esc_html( ucfirst( $log->status ) ); ?>
							</span>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr>
					<td colspan="10"><?php esc_html_e( 'No log entries found.', 'inkbridge-gen' ); ?></td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>

	<!-- Pagination -->
	<?php if ( $total_pages > 1 ) : ?>
		<div class="tablenav bottom">
			<div class="tablenav-pages">
				<span class="displaying-num">
					<?php
					printf(
						/* translators: %s: number of items */
						esc_html( _n( '%s item', '%s items', $total_logs, 'inkbridge-gen' ) ),
						number_format_i18n( $total_logs )
					);
					?>
				</span>
				<span class="pagination-links">
					<?php if ( $current_page_num > 1 ) : ?>
						<a class="first-page button" href="<?php echo esc_url( add_query_arg( 'paged', 1, $base_url ) ); ?>">
							&laquo;
						</a>
						<a class="prev-page button" href="<?php echo esc_url( add_query_arg( array( 'paged' => $current_page_num - 1, 'range' => $current_range, 'provider' => $current_provider, 'status' => $current_status ), $base_url ) ); ?>">
							&lsaquo;
						</a>
					<?php else : ?>
						<span class="tablenav-pages-navspan button disabled">&laquo;</span>
						<span class="tablenav-pages-navspan button disabled">&lsaquo;</span>
					<?php endif; ?>

					<span class="paging-input">
						<?php echo esc_html( $current_page_num ); ?>
						<?php esc_html_e( 'of', 'inkbridge-gen' ); ?>
						<span class="total-pages"><?php echo esc_html( $total_pages ); ?></span>
					</span>

					<?php if ( $current_page_num < $total_pages ) : ?>
						<a class="next-page button" href="<?php echo esc_url( add_query_arg( array( 'paged' => $current_page_num + 1, 'range' => $current_range, 'provider' => $current_provider, 'status' => $current_status ), $base_url ) ); ?>">
							&rsaquo;
						</a>
						<a class="last-page button" href="<?php echo esc_url( add_query_arg( array( 'paged' => $total_pages, 'range' => $current_range, 'provider' => $current_provider, 'status' => $current_status ), $base_url ) ); ?>">
							&raquo;
						</a>
					<?php else : ?>
						<span class="tablenav-pages-navspan button disabled">&rsaquo;</span>
						<span class="tablenav-pages-navspan button disabled">&raquo;</span>
					<?php endif; ?>
				</span>
			</div>
		</div>
	<?php endif; ?>

</div>
