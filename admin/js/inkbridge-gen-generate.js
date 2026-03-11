(function($) {
	'use strict';

	var $progress = $('#inkbridge-gen-progress');
	var $results  = $('#inkbridge-gen-results');
	var pollTimer = null;
	var currentItemId = 0;

	function getFormData() {
		var languages = [];
		$('input[name="languages[]"]:checked').each(function() {
			languages.push($(this).val());
		});

		return {
			topic:          $('#inkbridge-gen-topic').val(),
			pillar:         $('#inkbridge-gen-pillar').val(),
			word_count:     parseInt($('#inkbridge-gen-word-count').val()) || 0,
			extra_context:  $('#inkbridge-gen-extra-context').val(),
			status:         $('input[name="post_status"]:checked').val() || 'draft',
			languages:      languages,
			skip_image:     $('input[name="skip_image"]').is(':checked'),
			text_provider:  $('#inkbridge-gen-text-provider-override').val() || '',
			image_provider: $('#inkbridge-gen-image-provider-override').val() || ''
		};
	}

	function showProgress(state, message) {
		var icon, stateClass;
		if (state === 'queued') {
			stateClass = 'active';
			icon = '<span class="spinner is-active" style="float:none;margin:0"></span>';
		} else if (state === 'processing') {
			stateClass = 'active';
			icon = '<span class="spinner is-active" style="float:none;margin:0"></span>';
		} else if (state === 'completed') {
			stateClass = 'done';
			icon = '<span class="dashicons dashicons-yes-alt" style="color:#00a32a"></span>';
		} else if (state === 'failed') {
			stateClass = 'error';
			icon = '<span class="dashicons dashicons-dismiss" style="color:#d63638"></span>';
		}

		var html = '<div class="inside">';
		html += '<div class="inkbridge-gen-progress-step ' + stateClass + '">';
		html += '<div class="step-icon">' + icon + '</div>';
		html += '<div class="step-label">' + message + '</div>';
		html += '</div></div>';
		$progress.html(html).show();
	}

	function startPolling(itemId) {
		currentItemId = itemId;
		showProgress('queued', inkbridgeGen.strings.queued || 'Queued for generation...');

		pollTimer = setInterval(function() {
			$.post(inkbridgeGen.ajaxUrl, {
				action:  'inkbridge_gen_queue_status',
				nonce:   inkbridgeGen.nonce,
				item_id: itemId
			}).done(function(response) {
				if (!response.success) {
					stopPolling();
					showProgress('failed', response.data && response.data.message ? response.data.message : inkbridgeGen.strings.error);
					resetGenerateButton();
					return;
				}

				var data = response.data;
				if (data.status === 'processing') {
					showProgress('processing', inkbridgeGen.strings.processing || 'Generating article in background...');
				} else if (data.status === 'completed') {
					stopPolling();
					showProgress('completed', inkbridgeGen.strings.complete || 'Complete!');
					renderResults(data.result);
					resetGenerateButton();
				} else if (data.status === 'failed') {
					stopPolling();
					showProgress('failed', (inkbridgeGen.strings.error || 'Error') + ': ' + (data.error || 'Unknown error'));
					resetGenerateButton();
				}
				// If still 'pending', keep polling.
			}).fail(function() {
				stopPolling();
				showProgress('failed', 'Failed to check status.');
				resetGenerateButton();
			});
		}, 5000);
	}

	function stopPolling() {
		if (pollTimer) {
			clearInterval(pollTimer);
			pollTimer = null;
		}
		currentItemId = 0;
	}

	function resetGenerateButton() {
		$('#inkbridge-gen-generate-btn').prop('disabled', false).text(inkbridgeGen.strings.generate || 'Generate Article');
	}

	function renderResults(result) {
		if (!result) {
			$results.hide();
			return;
		}

		var html = '<div class="inside">';

		if (result.posts && Object.keys(result.posts).length > 0) {
			html += '<h3>' + (inkbridgeGen.strings.complete || 'Complete!') + '</h3>';
			$.each(result.posts, function(lang, post) {
				html += '<div class="inkbridge-gen-result-row">';
				html += '<div><strong>' + lang.toUpperCase() + ':</strong> ' + $('<span>').text(post.title).html();
				html += ' <span class="inkbridge-gen-badge inkbridge-gen-badge-' + post.status + '">' + post.status + '</span></div>';
				html += '<div class="result-actions">';
				if (post.url) html += '<a href="' + post.url + '" target="_blank" class="button button-small">View</a> ';
				if (post.edit_url) html += '<a href="' + post.edit_url + '" target="_blank" class="button button-small">Edit</a>';
				html += '</div></div>';
			});

			if (result.errors && Object.keys(result.errors).length > 0) {
				html += '<h4 style="color:#d63638">' + (inkbridgeGen.strings.error || 'Error') + '</h4>';
				$.each(result.errors, function(lang, message) {
					html += '<div style="color:#d63638">' + lang.toUpperCase() + ': ' + $('<span>').text(message).html() + '</div>';
				});
			}
		} else {
			html += '<h3 style="color:#d63638">' + (inkbridgeGen.strings.error || 'Error') + '</h3>';
			if (result.errors && Object.keys(result.errors).length > 0) {
				$.each(result.errors, function(lang, message) {
					html += '<div style="color:#d63638">' + lang.toUpperCase() + ': ' + $('<span>').text(message).html() + '</div>';
				});
			} else {
				html += '<p>No posts were created. Check the Logs page for details.</p>';
			}
		}

		html += '</div>';
		$results.html(html).show();
	}

	// Generate button click — add to queue and poll.
	$(document).on('click', '#inkbridge-gen-generate-btn', function(e) {
		e.preventDefault();

		var formData = getFormData();
		if (!formData.topic) {
			alert('Please enter a topic.');
			return;
		}
		if (!formData.pillar) {
			alert('Please select a content pillar.');
			return;
		}

		$(this).prop('disabled', true).text(inkbridgeGen.strings.generating || 'Generating...');
		$results.hide().empty();

		// Submit to queue via AJAX.
		$.post(inkbridgeGen.ajaxUrl, {
			action:         'inkbridge_gen_generate_queued',
			nonce:          inkbridgeGen.nonce,
			topic:          formData.topic,
			pillar:         formData.pillar,
			word_count:     formData.word_count,
			extra_context:  formData.extra_context,
			status:         formData.status,
			'languages[]':  formData.languages,
			skip_image:     formData.skip_image ? 1 : 0,
			text_provider:  formData.text_provider,
			image_provider: formData.image_provider
		}).done(function(response) {
			if (response.success) {
				startPolling(response.data.item_id);
			} else {
				showProgress('failed', response.data && response.data.message ? response.data.message : inkbridgeGen.strings.error);
				resetGenerateButton();
			}
		}).fail(function(xhr) {
			showProgress('failed', 'Request failed: ' + (xhr.statusText || 'Unknown error'));
			resetGenerateButton();
		});
	});

	// Add to Queue button (no immediate processing).
	$(document).on('click', '#inkbridge-gen-queue-btn', function(e) {
		e.preventDefault();

		var formData = getFormData();
		if (!formData.topic || !formData.pillar) {
			alert('Please enter a topic and select a pillar.');
			return;
		}

		var items = [{
			topic: formData.topic,
			pillar: formData.pillar,
			word_count: formData.word_count,
			languages: formData.languages,
			extra_context: formData.extra_context
		}];

		InkbridgeGen.ajax('inkbridge_gen_import_queue', null, { topics: JSON.stringify(items) })
			.done(function(response) {
				if (response.success) {
					InkbridgeGen.notify(response.data.message);
					$('#inkbridge-gen-topic').val('');
				} else {
					InkbridgeGen.notify(response.data.message, 'error');
				}
			});
	});

})(jQuery);
