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
			eventProxy: new Backbone.Model({}),
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
					!_.isEmpty(inst.options[type])
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
							$button.text('Ajax Error');
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
			},
			extractItemDepth: function($item){
				var match = String($item.attr('class')).match(/ ?menu-item-depth-(\d+) ?/);
				return match ? parseInt(match[1], 10) : 0;
			},
			updateItemsTreeUi: function ($item) {
				var itemDepth = inst.extractItemDepth($item);

				// Update all sub-items (until we reach a higher level item level)
				do {
					_.defer(inst.updateUi, $item);
					$item = $item.next();
				} while ($item.length && inst.extractItemDepth($item) > itemDepth);
			}
		};

		// Add ui elements on item box open
		$('#update-nav-menu').on('click', '.menu-item > .menu-item-bar .item-edit', function(){
			_.defer(inst.updateUi, $(this).closest('.menu-item'));
		});

		// Update UI on "Use as MegaMenu" change
		$('#update-nav-menu').on('change', '.menu-item > .menu-item-settings input.mega-menu-enabled', function () {
			_.defer(inst.updateItemsTreeUi, $(this).closest('.menu-item'));
		});

		// Items moving has stopped
		$('#update-nav-menu').on('sortstop', function (e, s) {
			_.defer(inst.updateItemsTreeUi, $(s.item));
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
