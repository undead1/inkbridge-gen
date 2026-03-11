(function($) {
	'use strict';

	// Save settings.
	$(document).on('click', '.inkbridge-gen-save-settings', function(e) {
		e.preventDefault();
		var $btn = $(this);
		var section = $btn.data('section');
		var $form = $btn.closest('.inkbridge-gen-settings-form');

		$btn.prop('disabled', true).text('Saving...');

		var settings = collectFormData($form, section);

		InkbridgeGen.ajax('inkbridge_gen_save_settings', null, {
			section: section,
			settings: JSON.stringify(settings)
		})
		.done(function(response) {
			if (response.success) {
				InkbridgeGen.notify(response.data.message);
			} else {
				InkbridgeGen.notify(response.data.message, 'error');
			}
		})
		.fail(function() {
			InkbridgeGen.notify('Failed to save settings.', 'error');
		})
		.always(function() {
			$btn.prop('disabled', false).text('Save Settings');
		});
	});

	function collectFormData($form, section) {
		var data = {};

		switch (section) {
			case 'general':
				data.default_word_count = $form.find('[name="default_word_count"]').val();
				data.default_post_status = $form.find('[name="default_post_status"]').val();
				data.default_author_id = $form.find('[name="default_author_id"]').val();
				data.schedule_delay_hours = $form.find('[name="schedule_delay_hours"]').val();
				data.translation_meta_key = $form.find('[name="translation_meta_key"]').val();
				break;

			case 'text_providers':
				data.active_text_provider = $form.find('[name="active_text_provider"]:checked').val();
				data.text_providers = {};
				data.api_keys = {};
				['openai', 'claude', 'gemini'].forEach(function(pid) {
					data.text_providers[pid] = {
						model: $form.find('[name="text_providers[' + pid + '][model]"]').val(),
						max_tokens: $form.find('[name="text_providers[' + pid + '][max_tokens]"]').val()
					};
					var keyVal = $form.find('[name="api_keys[' + pid + ']"]').val();
					if (keyVal) data.api_keys[pid] = keyVal;
				});
				break;

			case 'image_providers':
				data.active_image_provider = $form.find('[name="active_image_provider"]:checked').val();
				data.image_orientation = $form.find('[name="image_orientation"]:checked').val();
				data.image_search_suffix = $form.find('[name="image_search_suffix"]').val();
				data.api_keys = {};
				['unsplash', 'shutterstock', 'depositphotos'].forEach(function(pid) {
					var keyVal = $form.find('[name="api_keys[' + pid + ']"]').val();
					if (keyVal) data.api_keys[pid] = keyVal;
				});
				break;

			case 'languages':
				data.languages = [];
				$form.find('.inkbridge-gen-language-row').each(function() {
					data.languages.push({
						code: $(this).find('[name="lang_code"]').val(),
						name: $(this).find('[name="lang_name"]').val(),
						hreflang: $(this).find('[name="lang_hreflang"]').val(),
						parent_category: $(this).find('[name="lang_parent_category"]').val(),
						is_source: $(this).find('[name="lang_is_source"]').is(':checked')
					});
				});
				break;

			case 'pillars':
				data.pillars = [];
				$form.find('.inkbridge-gen-pillar-row').each(function() {
					var pillar = {
						key: $(this).find('[name="pillar_key"]').val(),
						label: $(this).find('[name="pillar_label"]').val(),
						context: $(this).find('[name="pillar_context"]').val(),
						categories: {}
					};
					$(this).find('[data-lang-cat]').each(function() {
						pillar.categories[$(this).data('lang-cat')] = $(this).val();
					});
					data.pillars.push(pillar);
				});
				break;

			case 'prompts':
				data.prompt_generate_system = $form.find('[name="prompt_generate_system"]').val();
				data.prompt_generate_user = $form.find('[name="prompt_generate_user"]').val();
				data.prompt_translate_system = $form.find('[name="prompt_translate_system"]').val();
				data.prompt_translate_user = $form.find('[name="prompt_translate_user"]').val();
				break;

			case 'scheduling':
				data.cron_enabled = $form.find('[name="cron_enabled"]').is(':checked');
				data.cron_frequency = $form.find('[name="cron_frequency"]').val();
				data.cron_max_per_run = $form.find('[name="cron_max_per_run"]').val();
				// Auto-generate settings.
				data.autogen_enabled = $form.find('[name="autogen_enabled"]').is(':checked');
				data.autogen_pillars = [];
				$form.find('[name="autogen_pillars[]"]:checked').each(function() {
					data.autogen_pillars.push($(this).val());
				});
				data.autogen_frequency = $form.find('[name="autogen_frequency"]').val();
				data.autogen_time = $form.find('[name="autogen_time"]').val();
				data.autogen_count = $form.find('[name="autogen_count"]').val();
				data.autogen_word_count = $form.find('[name="autogen_word_count"]').val();
				data.autogen_post_status = $form.find('[name="autogen_post_status"]:checked').val();
				break;
		}

		return data;
	}

	// Test provider connection.
	$(document).on('click', '.inkbridge-gen-test-provider', function(e) {
		e.preventDefault();
		var $btn = $(this);
		var providerId = $btn.data('provider');
		var providerType = $btn.data('type') || 'text';
		var apiKey = $btn.closest('.inkbridge-gen-provider-config').find('input[type="password"]').val();
		var $result = $btn.siblings('.inkbridge-gen-test-result');

		$btn.prop('disabled', true);
		$result.text(inkbridgeGen.strings.testing).removeClass('success error');

		InkbridgeGen.ajax('inkbridge_gen_test_provider', null, {
			provider_id: providerId,
			provider_type: providerType,
			api_key: apiKey
		})
		.done(function(response) {
			if (response.success) {
				$result.text(inkbridgeGen.strings.connection_ok).addClass('success');
			} else {
				$result.text(response.data.message).addClass('error');
			}
		})
		.fail(function() {
			$result.text(inkbridgeGen.strings.connection_failed).addClass('error');
		})
		.always(function() {
			$btn.prop('disabled', false);
		});
	});

	// Add language row.
	$(document).on('click', '#inkbridge-gen-add-language', function(e) {
		e.preventDefault();
		var $tbody = $(this).closest('.inkbridge-gen-settings-form').find('.inkbridge-gen-languages-tbody');
		var html = '<tr class="inkbridge-gen-language-row">' +
			'<td><input type="text" name="lang_code" value="" size="4" /></td>' +
			'<td><input type="text" name="lang_name" value="" /></td>' +
			'<td><input type="text" name="lang_hreflang" value="" size="8" /></td>' +
			'<td><input type="text" name="lang_parent_category" value="" size="10" /></td>' +
			'<td><input type="checkbox" name="lang_is_source" /></td>' +
			'<td><a href="#" class="inkbridge-gen-remove-row remove-row">&times;</a></td>' +
			'</tr>';
		$tbody.append(html);
	});

	// Add pillar row.
	$(document).on('click', '#inkbridge-gen-add-pillar', function(e) {
		e.preventDefault();
		var $tbody = $(this).closest('.inkbridge-gen-settings-form').find('.inkbridge-gen-pillars-tbody');
		var langCodes = [];
		$('.inkbridge-gen-languages-tbody .inkbridge-gen-language-row').each(function() {
			langCodes.push($(this).find('[name="lang_code"]').val());
		});

		var html = '<tr class="inkbridge-gen-pillar-row">' +
			'<td><input type="text" name="pillar_key" value="" size="10" /></td>' +
			'<td><input type="text" name="pillar_label" value="" /></td>';
		langCodes.forEach(function(code) {
			html += '<td><input type="text" data-lang-cat="' + code + '" value="" size="12" /></td>';
		});
		html += '<td><textarea name="pillar_context" rows="2" cols="30"></textarea></td>' +
			'<td><a href="#" class="inkbridge-gen-remove-row remove-row">&times;</a></td>' +
			'</tr>';
		$tbody.append(html);
	});

	// Generate pillars from categories.
	$(document).on('click', '#inkbridge-gen-generate-pillars', function(e) {
		e.preventDefault();
		var $btn = $(this);

		// Show inline WP-style confirmation if not yet confirmed.
		if (!$btn.data('confirmed')) {
			var $confirm = $('<div class="notice notice-warning inline inkbridge-gen-generate-confirm" style="margin:12px 0;padding:10px 14px;display:flex;align-items:center;gap:12px;">' +
				'<p style="margin:0;flex:1;">This will add/update pillars based on your WordPress categories using AI.</p>' +
				'<button type="button" class="button button-primary inkbridge-gen-confirm-yes">Continue</button>' +
				'<button type="button" class="button inkbridge-gen-confirm-no">Cancel</button>' +
				'</div>');
			$btn.closest('p').after($confirm);
			$btn.prop('disabled', true);

			$confirm.on('click', '.inkbridge-gen-confirm-yes', function() {
				$confirm.remove();
				$btn.data('confirmed', true).prop('disabled', false).trigger('click');
			});
			$confirm.on('click', '.inkbridge-gen-confirm-no', function() {
				$confirm.remove();
				$btn.prop('disabled', false);
			});
			return;
		}

		$btn.data('confirmed', false);
		var $status = $('#inkbridge-gen-generate-pillars-status');
		var $tbody = $('#inkbridge-gen-pillars-body');

		$btn.prop('disabled', true);
		$status.text('Generating...').removeClass('success error');

		InkbridgeGen.ajax('inkbridge_gen_generate_pillars')
		.done(function(response) {
			if (response.success && response.data.pillars) {
				var pillars = response.data.pillars;

				// Get language codes from existing header or language settings.
				var langCodes = [];
				$('#inkbridge-gen-pillars-table thead th').each(function() {
					var text = $(this).text();
					var match = text.match(/Category \((.+)\)/);
					if (match) langCodes.push(match[1]);
				});

				pillars.forEach(function(pillar) {
					// Check if a row with this key already exists.
					var $existingRow = null;
					$tbody.find('tr').each(function() {
						var $row = $(this);
						var keyInput = $row.find('input[name*="[key]"], [name="pillar_key"]');
						if (keyInput.length && keyInput.val() === pillar.key) {
							$existingRow = $row;
							return false;
						}
					});

					if ($existingRow) {
						// Update existing row.
						$existingRow.find('input[name*="[label]"], [name="pillar_label"]').val(pillar.label);
						$existingRow.find('textarea[name*="[context]"], [name="pillar_context"]').val(pillar.context || '');
						// Update category mappings.
						langCodes.forEach(function(code) {
							var slug = (pillar.categories && pillar.categories[code]) || '';
							$existingRow.find('input[name*="[categories][' + code + ']"], [data-lang-cat="' + code + '"]').val(slug);
						});
					} else {
						// Create new row matching the Add Pillar pattern.
						var html = '<tr class="inkbridge-gen-pillar-row">' +
							'<td><input type="text" name="pillar_key" value="' + $('<span>').text(pillar.key).html() + '" size="10" /></td>' +
							'<td><input type="text" name="pillar_label" value="' + $('<span>').text(pillar.label).html() + '" /></td>';
						langCodes.forEach(function(code) {
							var slug = (pillar.categories && pillar.categories[code]) || '';
							html += '<td><input type="text" data-lang-cat="' + code + '" value="' + $('<span>').text(slug).html() + '" size="12" /></td>';
						});
						html += '<td><textarea name="pillar_context" rows="2" cols="30">' + $('<span>').text(pillar.context || '').html() + '</textarea></td>' +
							'<td><a href="#" class="inkbridge-gen-remove-row remove-row">&times;</a></td>' +
							'</tr>';
						$tbody.append(html);
					}
				});

				$status.text(response.data.message).addClass('success');
				InkbridgeGen.notify(response.data.message);
			} else {
				var msg = (response.data && response.data.message) || 'Failed to generate pillars.';
				$status.text(msg).addClass('error');
				InkbridgeGen.notify(msg, 'error');
			}
		})
		.fail(function() {
			$status.text('Request failed.').addClass('error');
			InkbridgeGen.notify('Failed to generate pillars.', 'error');
		})
		.always(function() {
			$btn.prop('disabled', false);
		});
	});

	// Remove row.
	$(document).on('click', '.inkbridge-gen-remove-row', function(e) {
		e.preventDefault();
		$(this).closest('tr').remove();
	});

	// Reset prompts to defaults.
	$(document).on('click', '#inkbridge-gen-reset-prompts', function(e) {
		e.preventDefault();
		if (!confirm('Reset all prompts to defaults? This cannot be undone.')) return;
		// Reload page to get defaults from PHP.
		location.reload();
	});

})(jQuery);
