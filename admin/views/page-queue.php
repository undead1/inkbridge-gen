<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<?php if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Permission denied.', 'inkbridge-gen' ) ); } ?>
<?php
$settings = new Inkbridge_Gen_Settings();
$db       = new Inkbridge_Gen_DB();

$queue_counts = $db->get_queue_counts();
$queue_items  = $db->get_queue_items( array( 'per_page' => 50 ) );
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Queue Management', 'inkbridge-gen' ); ?></h1>

	<!-- Stats Cards -->
	<div class="inkbridge-gen-stats-row">
		<div class="inkbridge-gen-stat-card">
			<h3><?php esc_html_e( 'Pending', 'inkbridge-gen' ); ?></h3>
			<span class="inkbridge-gen-stat-number"><?php echo esc_html( number_format_i18n( $queue_counts['pending'] ) ); ?></span>
		</div>
		<div class="inkbridge-gen-stat-card">
			<h3><?php esc_html_e( 'Processing', 'inkbridge-gen' ); ?></h3>
			<span class="inkbridge-gen-stat-number"><?php echo esc_html( number_format_i18n( $queue_counts['processing'] ) ); ?></span>
		</div>
		<div class="inkbridge-gen-stat-card">
			<h3><?php esc_html_e( 'Completed', 'inkbridge-gen' ); ?></h3>
			<span class="inkbridge-gen-stat-number"><?php echo esc_html( number_format_i18n( $queue_counts['completed'] ) ); ?></span>
		</div>
		<div class="inkbridge-gen-stat-card">
			<h3><?php esc_html_e( 'Failed', 'inkbridge-gen' ); ?></h3>
			<span class="inkbridge-gen-stat-number"><?php echo esc_html( number_format_i18n( $queue_counts['failed'] ) ); ?></span>
		</div>
	</div>

	<!-- Action Buttons -->
	<div class="inkbridge-gen-queue-actions">
		<button type="button" id="inkbridge-gen-import-json-btn" class="button button-secondary">
			<?php esc_html_e( 'Import JSON', 'inkbridge-gen' ); ?>
		</button>
		<button type="button" id="inkbridge-gen-process-next-btn" class="button button-primary">
			<?php esc_html_e( 'Process Next', 'inkbridge-gen' ); ?>
		</button>
		<button type="button" id="inkbridge-gen-clear-completed-btn" class="button button-secondary">
			<?php esc_html_e( 'Clear Completed', 'inkbridge-gen' ); ?>
		</button>
	</div>

	<!-- Import Modal (hidden) -->
	<div id="inkbridge-gen-import-modal" class="inkbridge-gen-modal" style="display: none;">
		<div class="inkbridge-gen-modal-content">
			<div class="inkbridge-gen-modal-header">
				<h2><?php esc_html_e( 'Import Topics from JSON', 'inkbridge-gen' ); ?></h2>
				<button type="button" class="inkbridge-gen-modal-close">&times;</button>
			</div>
			<div class="inkbridge-gen-modal-body">
				<p class="description">
					<?php esc_html_e( 'Paste a JSON array of topic objects. Expected format:', 'inkbridge-gen' ); ?>
				</p>
				<pre><code>[
  {
    "topic": "Article Topic Here",
    "pillar": "pillar-key",
    "word_count": 1500,
    "languages": ["en", "ms"],
    "extra_context": "Optional context"
  }
]</code></pre>
				<textarea id="inkbridge-gen-import-json" class="large-text" rows="10" placeholder='[{"topic": "...", "pillar": "..."}]'></textarea>
			</div>
			<div class="inkbridge-gen-modal-footer">
				<button type="button" id="inkbridge-gen-import-btn" class="button button-primary">
					<?php esc_html_e( 'Import', 'inkbridge-gen' ); ?>
				</button>
				<button type="button" class="button button-secondary inkbridge-gen-modal-close">
					<?php esc_html_e( 'Cancel', 'inkbridge-gen' ); ?>
				</button>
			</div>
		</div>
	</div>

	<!-- Queue Table -->
	<table class="widefat striped inkbridge-gen-queue-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'ID', 'inkbridge-gen' ); ?></th>
				<th><?php esc_html_e( 'Topic', 'inkbridge-gen' ); ?></th>
				<th><?php esc_html_e( 'Pillar', 'inkbridge-gen' ); ?></th>
				<th><?php esc_html_e( 'Status', 'inkbridge-gen' ); ?></th>
				<th><?php esc_html_e( 'Created', 'inkbridge-gen' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'inkbridge-gen' ); ?></th>
			</tr>
		</thead>
		<tbody id="inkbridge-gen-queue-body">
			<?php if ( ! empty( $queue_items ) ) : ?>
				<?php foreach ( $queue_items as $item ) : ?>
					<tr data-id="<?php echo esc_attr( $item->id ); ?>">
						<td><?php echo esc_html( $item->id ); ?></td>
						<td><?php echo esc_html( $item->topic ); ?></td>
						<td><?php echo esc_html( $item->pillar ); ?></td>
						<td>
							<span class="inkbridge-gen-badge inkbridge-gen-badge-<?php echo esc_attr( $item->status ); ?>">
								<?php echo esc_html( ucfirst( $item->status ) ); ?>
							</span>
						</td>
						<td><?php echo esc_html( $item->created_at ); ?></td>
						<td>
							<?php if ( 'failed' === $item->status ) : ?>
								<button type="button" class="button button-small inkbridge-gen-retry-item" data-id="<?php echo esc_attr( $item->id ); ?>">
									<?php esc_html_e( 'Retry', 'inkbridge-gen' ); ?>
								</button>
							<?php endif; ?>
							<button type="button" class="button button-small inkbridge-gen-delete-item" data-id="<?php echo esc_attr( $item->id ); ?>">
								<?php esc_html_e( 'Delete', 'inkbridge-gen' ); ?>
							</button>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr>
					<td colspan="6"><?php esc_html_e( 'No items in the queue.', 'inkbridge-gen' ); ?></td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>

</div>
