jQuery(function ($) {

	var localized = _fw_ext_mega_menu;

	// Screen Options: Show advanced menu properties: Icon Checkbox
	(function () {

		var container = '#menu-to-edit';
		var selector = '.hide-column-tog[name="icon-hide"]'; // WP 4.4+

		$(document).on('change', selector, function () {
			$(container).toggleClass('screen-options-icon', $(this).is(':checked'));
		});

		$(selector).trigger('change');
	})();

	// Mega Menu Column Title: input
	(function (selector) {

		$(document).on('change', selector, function () {
			$(this).closest('li').find(selector).val($(this).val());
		});
		// $(selector).trigger('change') is not necessary since those two fields
		// are populated by WordPress with the same value (title)

	})('.mega-menu-title, .edit-menu-item-title');

	// Mega Menu Column Title: checkbox
	(function (selector) {

		$(document).on('change', selector, function () {
			var checkbox = $(this);
			checkbox.closest('p').find('.mega-menu-title').prop('readonly', checkbox.is(':checked'));
		});
		$(selector).trigger('change');

	})('.mega-menu-title-off');

	// Use as Mega Menu Checkbox
	(function () {

		var menu = $('#menu-to-edit');

		function update()
		{
			menu.children().removeClass('mega-menu');
			menu.find('.mega-menu-title').prop('disabled', true);
			menu.find('.edit-menu-item-title').prop('disabled', false);
			menu.children('.menu-item-depth-0:has(.mega-menu-enabled:checked)').each(function () {
				var item = $(this);
				item.addClass('mega-menu');
				item.nextUntil('.menu-item-depth-0').addClass('mega-menu');
				item.siblings('.mega-menu').find('.mega-menu-title').prop('disabled', false);
				item.siblings('.mega-menu').find('.edit-menu-item-title').prop('disabled', true);
			});
		}

		$(document).on('change', '.menu-item-depth-0 .mega-menu-enabled', update);
		// FIXME our handler should be called after WP handler
		menu.on('sortstop', function () {
			setTimeout(update, 1);
		});

		update();

	})();

	// ---- Export / Import layout (buttons injected on the enabled top item, see updateUi) ----
	// Export → download the item's layout (row options + child columns/items) as a JSON file.
	$(document).on('click', '.fw-mm-export', function (e) {
		e.preventDefault();
		var id = $(this).closest('.menu-item').find('input.menu-item-data-db-id:first').val();
		if (!id) { return; }
		$.ajax({
			url: ajaxurl, method: 'post', dataType: 'json',
			data: { action: 'fw_ext_megamenu_export_layout', _ajax_nonce: localized.nonce, id: id }
		}).done(function (r) {
			if (r && r.success && r.data && r.data.layout) {
				var blob = new Blob([JSON.stringify(r.data.layout, null, '\t')], { type: 'application/json' });
				var a = document.createElement('a');
				a.href = URL.createObjectURL(blob);
				a.download = r.data.filename || 'mega-menu-layout.json';
				document.body.appendChild(a); a.click();
				setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); }, 100);
			} else {
				window.alert((r && r.data && r.data.message) || (localized.l10n && localized.l10n.export_fail) || 'Export failed.');
			}
		});
	});
	// Import ← upload a previously exported JSON, re-creating the columns/items beneath this item.
	$(document).on('click', '.fw-mm-import', function (e) {
		e.preventDefault();
		var id = $(this).closest('.menu-item').find('input.menu-item-data-db-id:first').val();
		if (!id) { return; }
		var input = document.createElement('input');
		input.type = 'file'; input.accept = '.json,application/json';
		input.onchange = function () {
			var file = input.files && input.files[0];
			if (!file) { return; }
			var reader = new FileReader();
			reader.onload = function () {
				$.ajax({
					url: ajaxurl, method: 'post', dataType: 'json',
					data: { action: 'fw_ext_megamenu_import_layout', _ajax_nonce: localized.nonce, id: id, layout: reader.result }
				}).done(function (r) {
					if (r && r.success) {
						window.alert(((localized.l10n && localized.l10n.import_done) || 'Imported %d item(s). Reloading…').replace('%d', r.data.created));
						location.reload();
					} else {
						window.alert((r && r.data && r.data.message) || (localized.l10n && localized.l10n.import_fail) || 'Import failed.');
					}
				});
			};
			reader.readAsText(file);
		};
		input.click();
	});

	// Preview → render the assembled panel (saved state) in an overlay.
	$(document).on('click', '.fw-mm-preview-btn', function (e) {
		e.preventDefault();
		var id = $(this).closest('.menu-item').find('input.menu-item-data-db-id:first').val();
		if (!id) { return; }
		var $ov = $('#fw-mm-preview-overlay');
		if (!$ov.length) {
			$ov = $('<div id="fw-mm-preview-overlay" class="fw-mm-preview-overlay"><div class="fw-mm-preview-box"><div class="fw-mm-preview-head"><span class="fw-mm-preview-title"></span><button type="button" class="button fw-mm-preview-close"></button></div><div class="fw-mm-preview fw-mm-preview-body"></div></div></div>');
			$('body').append($ov);
			$ov.find('.fw-mm-preview-title').text((localized.l10n && localized.l10n.preview_title) || 'Mega Menu preview');
			$ov.find('.fw-mm-preview-close').text((localized.l10n && localized.l10n.preview_close) || 'Close');
			$ov.on('click', function (ev) { if (ev.target === $ov[0]) { $ov.hide(); } });
			$ov.find('.fw-mm-preview-close').on('click', function () { $ov.hide(); });
		}
		var $body = $ov.find('.fw-mm-preview-body');
		$body.html('<span class="spinner is-active" style="float:none"></span>');
		$ov.css('display', 'flex');
		$.ajax({
			url: ajaxurl, method: 'post', dataType: 'json',
			data: { action: 'fw_ext_megamenu_preview_layout', _ajax_nonce: localized.nonce, id: id }
		}).done(function (r) {
			if (r && r.success && r.data && r.data.html) {
				$body.html('<ul class="primary-menu">' + r.data.html + '</ul>');
			} else {
				$body.text((r && r.data && r.data.message) || (localized.l10n && localized.l10n.preview_fail) || 'Preview failed.');
			}
		}).fail(function () {
			$body.text((localized.l10n && localized.l10n.preview_fail) || 'Preview failed.');
		});
	});

	// NOTE: the standalone icon picker was removed — the icon is now an option
	// inside the per-item "Settings" modal (icon-v2). See item.php / helpers.php.

	// The problem is in using **change** event for initialization.
	//
	// Internally WordPress listen this inputs for **change** event
	// and sets **menuChanged** flag. It also sets window.onbeforeunload handler
	// which decides whether or not display
	//
	//     "The changes you made will be lost if you navigate away from this page."
	//
	// dialog based on this flag.
	wpNavMenu.menusChanged = false;

	/**
	 * Item options
	 */
	(function(){
		var inst = {
			values: {},
			options: localized.options,
			modal_sizes: localized.item_options_modal_sizes,
			$input: null,
			// Save item values in form hidden input
			updateInput: function(){
				if (inst.$input === null) {
					inst.$input = $('<input type="hidden" name="fw-megamenu-items-values" value="[]" />');
					$('#update-nav-menu').append(inst.$input);
				}

				inst.$input.val(JSON.stringify(inst.values));
			},
			getItemType: function ($item) {
				var $parent = $item;

				// find all parents
				while (!$parent.hasClass('menu-item-depth-0')) {
					$parent = $parent.prev();
				}

				if (!$parent.find('input.mega-menu-enabled:first').is(':checked')) {
					return 'default'; // parent is not MegaMenu enabled
				}

				if ($item.hasClass('menu-item-depth-0')) {
					return 'row';
				} else if ($item.hasClass('menu-item-depth-1')) {
					return 'column';
				} else {
					return 'item';
				}
			},
			modal: new fw.OptionsModal({
				options: []
			}),
			/**
			 * A listener host, nothing more — it only ever calls listenTo /
			 * stopListening on inst.modal. This was `new Backbone.Model({})`,
			 * which pulled the whole of Backbone onto the nav-menus screen for
			 * an empty model. fw.Events (fw-oo.js, since 2.16.11) provides the
			 * same two methods and initialises its bookkeeping lazily, so a
			 * plain mixin object is a complete replacement.
			 */
			eventProxy: Object.assign({}, fw.Events),
			/**
			 * Remember ajax handlers and abort previous if a new one was requested
			 * On slow internet connection, when you will move an open menu tree and change the hierarchy
			 */
			ajaxHandlers: { values: {} },
			updateUi: function ($item) {
				var type, id = $item.find('input.menu-item-data-db-id:first').val();

				if (!(
					$item.hasClass('menu-item-edit-active') // the box is closed
					&&
					(type = inst.getItemType($item))
					&&
					// was !_.isEmpty(…) — the type has options registered
					inst.options[type]
					&&
					Object.keys(inst.options[type]).length > 0
				)) {
					if (typeof inst.ajaxHandlers.values[id] != 'undefined') {
						inst.ajaxHandlers.values[id].abort();
					}

					$item.find('button.fw-megamenu-stngs:first').remove();
					return;
				}

				var $button = $item.find('button.fw-megamenu-stngs:first');

				if (!$button.length) {
					$button = $('<button type="button" disabled="disabled" class="button fw-megamenu-stngs"></button>');
					$button.text(localized.l10n.item_options_btn);
					$button.attr('aria-label', localized.l10n.item_options_btn);

					$item.find('.field-mega-menu-settings:first').append($button);
				}

				if (typeof inst.values[id] !== 'undefined') {
					$button.removeAttr('disabled');
				} else {
					if (typeof inst.ajaxHandlers.values[id] !== 'undefined') {
						inst.ajaxHandlers.values[id].abort();
					}

					$button.attr('disabled', 'disabled');

					inst.ajaxHandlers.values[id] = $.ajax({
						url: ajaxurl,
						method: 'post',
						dataType: 'json',
						data: {
							action: 'fw_ext_megamenu_item_values',
							_ajax_nonce: localized.nonce,
							id: id
						}
					}).done(function (r) {
						if (r && r.success) {
							$button.removeAttr('disabled');
							inst.values[id] = r.data.values;
						} else {
							$button.text((localized.l10n && localized.l10n.ajax_error) || 'Ajax Error');
						}
					}).fail(function (x, y, error) {
						if ($button.length && $button.is(':visible')) { // may not exist
							$button.text(String(error));

							/**
							 * Remove the button so on next box open the init will try again to do the ajax
							 *
							 * Note: Do not retry ajax because it can be
							 *       a server problem and this will cause "DDOS" from all items
							 */
							setTimeout(function(){ $button.remove(); }, 3000);
						}
					}).always(function () {
						delete inst.ajaxHandlers.values[id];
					});
				}


				// Export / Import layout controls — only on the enabled top-level mega item.
				if (type === 'row' && !$item.find('.fw-mm-io:first').length) {
					var $io = $('<span class="fw-mm-io"></span>');
					$('<button type="button" class="button-link fw-mm-preview-btn"></button>')
						.text((localized.l10n && localized.l10n.preview_layout) || 'Preview').appendTo($io);
					$io.append(document.createTextNode(' · '));
					$('<button type="button" class="button-link fw-mm-export"></button>')
						.text((localized.l10n && localized.l10n.export_layout) || 'Export layout').appendTo($io);
					$io.append(document.createTextNode(' · '));
					$('<button type="button" class="button-link fw-mm-import"></button>')
						.text((localized.l10n && localized.l10n.import_layout) || 'Import layout').appendTo($io);
					$item.find('.field-mega-menu-settings:first').append($io);
				}
			},
			extractItemDepth: function($item){
				var match = String($item.attr('class')).match(/ ?menu-item-depth-(\d+) ?/);
				return match ? parseInt(match[1], 10) : 0;
			},
			updateItemsTreeUi: function ($item) {
				var itemDepth = inst.extractItemDepth($item);

				// Update all sub-items (until we reach a higher level item level)
				do {
					setTimeout(inst.updateUi, 0, $item);
					$item = $item.next();
				} while ($item.length && inst.extractItemDepth($item) > itemDepth);
			}
		};

		// Add ui elements on item box open
		$('#update-nav-menu').on('click', '.menu-item > .menu-item-bar .item-edit', function(){
			setTimeout(inst.updateUi, 0, $(this).closest('.menu-item'));
		});

		// Update UI on "Use as MegaMenu" change
		$('#update-nav-menu').on('change', '.menu-item > .menu-item-settings input.mega-menu-enabled', function () {
			setTimeout(inst.updateItemsTreeUi, 0, $(this).closest('.menu-item'));
		});

		// Items moving has stopped
		$('#update-nav-menu').on('sortstop', function (e, s) {
			setTimeout(inst.updateItemsTreeUi, 0, $(s.item));
		});

		// Prepare and open modal on button click
		$('#update-nav-menu').on('click', '.menu-item > .menu-item-settings button.fw-megamenu-stngs', function(){
			var $item = $(this).closest('.menu-item'),
				type = inst.getItemType($item),
				id = $item.find('input.menu-item-data-db-id:first').val();

			if (!type) {
				$(this).remove(); // button has remained visible because of a bug
				return;
			}

			{
				inst.eventProxy.stopListening(inst.modal);

				inst.modal.set('values', inst.values[id][type]);

				inst.eventProxy.listenTo(inst.modal, 'change:values', function(){
					inst.values[id][type] = inst.modal.get('values');
					inst.updateInput();
				});
			}

			inst.modal.set('options', inst.options[type]);
			inst.modal.set('size', inst.modal_sizes[type]);
			inst.modal.open();
		});
	})();
});
