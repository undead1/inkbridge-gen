(function($) {
	'use strict';

	window.InkbridgeGen = window.InkbridgeGen || {};

	InkbridgeGen.ajax = function(action, data, extraParams) {
		var params = {
			action: action,
			nonce: inkbridgeGen.nonce
		};
		if (data) {
			if (typeof data === 'object') {
				params.data = JSON.stringify(data);
			} else {
				params.data = data;
			}
		}
		if (extraParams) {
			$.extend(params, extraParams);
		}
		return $.post(inkbridgeGen.ajaxUrl, params);
	};

	InkbridgeGen.notify = function(message, type) {
		type = type || 'success';
		var $notice = $('<div class="inkbridge-gen-notice inkbridge-gen-notice-' + type + '">' + message + '</div>');
		$('.wrap h1').first().after($notice);
		setTimeout(function() { $notice.fadeOut(function() { $notice.remove(); }); }, 5000);
	};

	// Tab switching for settings page.
	$(document).on('click', '.inkbridge-gen-settings-tabs .nav-tab', function(e) {
		e.preventDefault();
		var tab = $(this).data('tab');
		$('.inkbridge-gen-settings-tabs .nav-tab').removeClass('nav-tab-active');
		$(this).addClass('nav-tab-active');
		$('.inkbridge-gen-tab-panel').hide();
		$('#inkbridge-gen-tab-' + tab).show();
		// Update URL without reload.
		var url = new URL(window.location);
		url.searchParams.set('tab', tab);
		history.replaceState(null, '', url);
	});

	// Advanced options toggle (postbox-style collapse).
	$(document).on('click', '.inkbridge-gen-advanced-options > .hndle', function(e) {
		e.preventDefault();
		var $postbox = $(this).closest('.inkbridge-gen-advanced-options');
		var $inside  = $postbox.find('> .inside');
		var isOpen   = $inside.is(':visible');

		$inside.slideToggle(200);
		$postbox.attr('aria-expanded', !isOpen);
	});

	// Suggest Topic button.
	$(document).on('click', '.inkbridge-gen-suggest-topic-btn', function(e) {
		e.preventDefault();

		var $btn    = $(this);
		var $label  = $btn.find('.inkbridge-gen-suggest-label');
		var $target = $($btn.data('target'));
		var $pillar = $($btn.data('pillar'));

		if ($btn.prop('disabled')) {
			return;
		}

		var pillarVal = $pillar.length ? $pillar.val() : '';
		var origText  = $label.text();

		$btn.prop('disabled', true);
		$label.text(inkbridgeGen.strings.suggesting || 'Suggesting...');

		InkbridgeGen.ajax('inkbridge_gen_suggest_topic', null, { pillar: pillarVal })
			.done(function(response) {
				if (response.success && response.data.topic) {
					$target.val(response.data.topic).trigger('change');
				} else {
					InkbridgeGen.notify(
						response.data && response.data.message
							? response.data.message
							: (inkbridgeGen.strings.suggest_error || 'Could not suggest a topic.'),
						'error'
					);
				}
			})
			.fail(function() {
				InkbridgeGen.notify(inkbridgeGen.strings.suggest_error || 'Could not suggest a topic.', 'error');
			})
			.always(function() {
				$btn.prop('disabled', false);
				$label.text(origText);
			});
	});

})(jQuery);
