(function($) {
	'use strict';

	// Import JSON modal.
	$(document).on('click', '#inkbridge-gen-import-btn', function(e) {
		e.preventDefault();
		$('.inkbridge-gen-modal-overlay').addClass('active');
	});

	$(document).on('click', '.inkbridge-gen-modal-close, .inkbridge-gen-modal-overlay', function(e) {
		if (e.target === this) {
			$('.inkbridge-gen-modal-overlay').removeClass('active');
		}
	});

	// Import topics.
	$(document).on('click', '#inkbridge-gen-import-submit', function(e) {
		e.preventDefault();
		var json = $('#inkbridge-gen-import-json').val();
		if (!json.trim()) return;

		$(this).prop('disabled', true).text('Importing...');

		InkbridgeGen.ajax('inkbridge_gen_import_queue', null, { topics: json })
			.done(function(response) {
				if (response.success) {
					InkbridgeGen.notify(response.data.message);
					$('.inkbridge-gen-modal-overlay').removeClass('active');
					$('#inkbridge-gen-import-json').val('');
					location.reload();
				} else {
					InkbridgeGen.notify(response.data.message, 'error');
				}
			})
			.always(function() {
				$('#inkbridge-gen-import-submit').prop('disabled', false).text('Import Topics');
			});
	});

	// Process next.
	$(document).on('click', '#inkbridge-gen-process-next', function(e) {
		e.preventDefault();
		$(this).prop('disabled', true).text('Processing...');

		InkbridgeGen.ajax('inkbridge_gen_process_queue_item', null, {})
			.done(function(response) {
				if (response.success) {
					InkbridgeGen.notify(response.data.message);
					location.reload();
				} else {
					InkbridgeGen.notify(response.data.message, 'error');
				}
			})
			.always(function() {
				$('#inkbridge-gen-process-next').prop('disabled', false).text('Process Next');
			});
	});

	// Clear completed.
	$(document).on('click', '#inkbridge-gen-clear-completed', function(e) {
		e.preventDefault();
		if (!confirm(inkbridgeGen.strings.confirm_clear)) return;

		InkbridgeGen.ajax('inkbridge_gen_clear_queue', null, { type: 'completed' })
			.done(function(response) {
				if (response.success) {
					InkbridgeGen.notify(response.data.message);
					location.reload();
				}
			});
	});

	// Delete queue item.
	$(document).on('click', '.inkbridge-gen-delete-queue', function(e) {
		e.preventDefault();
		if (!confirm(inkbridgeGen.strings.confirm_delete)) return;

		var id = $(this).data('id');
		var $row = $(this).closest('tr');

		InkbridgeGen.ajax('inkbridge_gen_delete_queue_item', null, { item_id: id })
			.done(function(response) {
				if (response.success) {
					$row.fadeOut(function() { $(this).remove(); });
				}
			});
	});

	// Retry queue item.
	$(document).on('click', '.inkbridge-gen-retry-queue', function(e) {
		e.preventDefault();
		var id = $(this).data('id');

		InkbridgeGen.ajax('inkbridge_gen_retry_queue_item', null, { item_id: id })
			.done(function(response) {
				if (response.success) {
					InkbridgeGen.notify(response.data.message);
					location.reload();
				}
			});
	});

})(jQuery);
