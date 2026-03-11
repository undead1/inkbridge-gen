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
				$('#inkbridge-gen-languages-body tr').each(function() {
					var $row = $(this);
					var inputs = $row.find('input');
					data.languages.push({
						code: inputs.filter('[name$="[code]"]').val(),
						name: inputs.filter('[name$="[name]"]').val(),
						hreflang: inputs.filter('[name$="[hreflang]"]').val(),
						parent_category: inputs.filter('[name$="[parent_category]"]').val(),
						is_source: inputs.filter('[name$="[is_source]"]').is(':checked')
					});
				});
				break;

			case 'pillars':
				data.pillars = [];
				$('#inkbridge-gen-pillars-body tr').each(function() {
					var $row = $(this);
					var pillar = {
						key: $row.find('input[name$="[key]"]').val(),
						label: $row.find('input[name$="[label]"]').val(),
						context: $row.find('textarea').val(),
						categories: {}
					};
					// Match inputs with name pattern like pillars[N][categories][en].
					$row.find('input[name*="[categories]"]').each(function() {
						var name = $(this).attr('name');
						var match = name.match(/\[categories\]\[(\w+)\]/);
						if (match) {
							pillar.categories[match[1]] = $(this).val();
						}
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
		var $tbody = $('#inkbridge-gen-languages-body');
		var index = $tbody.find('tr').length;
		var html = '<tr data-index="' + index + '">' +
			'<td><input type="text" name="languages[' + index + '][code]" class="small-text" placeholder="en" /></td>' +
			'<td><input type="text" name="languages[' + index + '][name]" class="regular-text" placeholder="English" /></td>' +
			'<td><input type="text" name="languages[' + index + '][hreflang]" class="small-text" placeholder="en-US" /></td>' +
			'<td><input type="text" name="languages[' + index + '][parent_category]" class="regular-text" placeholder="english" /></td>' +
			'<td><input type="checkbox" name="languages[' + index + '][is_source]" value="1" class="inkbridge-gen-source-checkbox" /></td>' +
			'<td><button type="button" class="button button-small inkbridge-gen-remove-language">Remove</button></td>' +
			'</tr>';
		$tbody.append(html);
	});

	// Add pillar row.
	$(document).on('click', '#inkbridge-gen-add-pillar', function(e) {
		e.preventDefault();
		var $tbody = $('#inkbridge-gen-pillars-body');
		var index = $tbody.find('tr').length;

		// Get language codes from pillar table headers.
		var langCodes = [];
		$('#inkbridge-gen-pillars-table thead th[data-lang-code]').each(function() {
			langCodes.push($(this).data('lang-code'));
		});

		var html = '<tr data-index="' + index + '">' +
			'<td><input type="text" name="pillars[' + index + '][key]" class="small-text" placeholder="pillar-key" /></td>' +
			'<td><input type="text" name="pillars[' + index + '][label]" class="regular-text" placeholder="Pillar Label" /></td>';
		langCodes.forEach(function(code) {
			html += '<td><input type="text" name="pillars[' + index + '][categories][' + code + ']" class="small-text" placeholder="' + code + '-slug" /></td>';
		});
		html += '<td><textarea name="pillars[' + index + '][context]" rows="2" class="large-text"></textarea></td>' +
			'<td><button type="button" class="button button-small inkbridge-gen-remove-pillar">Remove</button></td>' +
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

				// Get language codes from existing header.
				var langCodes = [];
				$('#inkbridge-gen-pillars-table thead th[data-lang-code]').each(function() {
					langCodes.push($(this).data('lang-code'));
				});

				var nextIndex = $tbody.find('tr').length;

				pillars.forEach(function(pillar) {
					// Check if a row with this key already exists.
					var $existingRow = null;
					$tbody.find('tr').each(function() {
						var $row = $(this);
						var keyInput = $row.find('input[name$="[key]"]');
						if (keyInput.length && keyInput.val() === pillar.key) {
							$existingRow = $row;
							return false;
						}
					});

					if ($existingRow) {
						// Update existing row.
						$existingRow.find('input[name$="[label]"]').val(pillar.label);
						$existingRow.find('textarea').val(pillar.context || '');
						// Update category mappings.
						langCodes.forEach(function(code) {
							var slug = (pillar.categories && pillar.categories[code]) || '';
							$existingRow.find('input[name*="[categories][' + code + ']"]').val(slug);
						});
					} else {
						// Create new row matching PHP template structure.
						var idx = nextIndex++;
						var html = '<tr data-index="' + idx + '">' +
							'<td><input type="text" name="pillars[' + idx + '][key]" class="small-text" value="' + $('<span>').text(pillar.key).html() + '" /></td>' +
							'<td><input type="text" name="pillars[' + idx + '][label]" class="regular-text" value="' + $('<span>').text(pillar.label).html() + '" /></td>';
						langCodes.forEach(function(code) {
							var slug = (pillar.categories && pillar.categories[code]) || '';
							html += '<td><input type="text" name="pillars[' + idx + '][categories][' + code + ']" class="small-text" value="' + $('<span>').text(slug).html() + '" /></td>';
						});
						html += '<td><textarea name="pillars[' + idx + '][context]" rows="2" class="large-text">' + $('<span>').text(pillar.context || '').html() + '</textarea></td>' +
							'<td><button type="button" class="button button-small inkbridge-gen-remove-pillar">Remove</button></td>' +
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

	// Generate languages from categories.
	$(document).on('click', '#inkbridge-gen-generate-languages', function(e) {
		e.preventDefault();
		var $btn = $(this);

		// Show inline WP-style confirmation if not yet confirmed.
		if (!$btn.data('confirmed')) {
			var $confirm = $('<div class="notice notice-warning inline inkbridge-gen-generate-confirm" style="margin:12px 0;padding:10px 14px;display:flex;align-items:center;gap:12px;">' +
				'<p style="margin:0;flex:1;">This will detect languages from your top-level WordPress categories using AI and add/update the languages table.</p>' +
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
		var $status = $('#inkbridge-gen-generate-languages-status');
		var $tbody = $('#inkbridge-gen-languages-body');

		$btn.prop('disabled', true);
		$status.text('Detecting languages...').removeClass('success error');

		InkbridgeGen.ajax('inkbridge_gen_generate_languages')
		.done(function(response) {
			if (response.success && response.data.languages) {
				var languages = response.data.languages;
				var nextIndex = $tbody.find('tr').length;

				// Uncheck all source checkboxes first.
				$tbody.find('input[name$="[is_source]"]').prop('checked', false);

				languages.forEach(function(lang) {
					// Check if a row with this code already exists.
					var $existingRow = null;
					$tbody.find('tr').each(function() {
						var $row = $(this);
						var codeInput = $row.find('input[name$="[code]"]');
						if (codeInput.length && codeInput.val() === lang.code) {
							$existingRow = $row;
							return false;
						}
					});

					if ($existingRow) {
						// Update existing row.
						$existingRow.find('input[name$="[name]"]').val(lang.name);
						$existingRow.find('input[name$="[hreflang]"]').val(lang.hreflang);
						$existingRow.find('input[name$="[parent_category]"]').val(lang.parent_category);
						$existingRow.find('input[name$="[is_source]"]').prop('checked', !!lang.is_source);
					} else {
						// Create new row matching PHP template structure.
						var idx = nextIndex++;
						var html = '<tr data-index="' + idx + '">' +
							'<td><input type="text" name="languages[' + idx + '][code]" class="small-text" value="' + $('<span>').text(lang.code).html() + '" /></td>' +
							'<td><input type="text" name="languages[' + idx + '][name]" class="regular-text" value="' + $('<span>').text(lang.name).html() + '" /></td>' +
							'<td><input type="text" name="languages[' + idx + '][hreflang]" class="small-text" value="' + $('<span>').text(lang.hreflang).html() + '" /></td>' +
							'<td><input type="text" name="languages[' + idx + '][parent_category]" class="regular-text" value="' + $('<span>').text(lang.parent_category).html() + '" /></td>' +
							'<td><input type="checkbox" name="languages[' + idx + '][is_source]" value="1" class="inkbridge-gen-source-checkbox"' + (lang.is_source ? ' checked' : '') + ' /></td>' +
							'<td><button type="button" class="button button-small inkbridge-gen-remove-language">Remove</button></td>' +
							'</tr>';
						$tbody.append(html);
					}
				});

				$status.text(response.data.message).addClass('success');
				InkbridgeGen.notify(response.data.message);
			} else {
				var msg = (response.data && response.data.message) || 'Failed to detect languages.';
				$status.text(msg).addClass('error');
				InkbridgeGen.notify(msg, 'error');
			}
		})
		.fail(function() {
			$status.text('Request failed.').addClass('error');
			InkbridgeGen.notify('Failed to detect languages.', 'error');
		})
		.always(function() {
			$btn.prop('disabled', false);
		});
	});

	// Remove row (languages and pillars).
	$(document).on('click', '.inkbridge-gen-remove-row, .inkbridge-gen-remove-language, .inkbridge-gen-remove-pillar', function(e) {
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
