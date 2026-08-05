<?php if (!defined('FW')) die('Forbidden');

// MegaMenu item options, column child, level 3+

// Palette-preset compact color control (project convention); raw color-picker
// fallback when the shortcodes helper isn't active.
$fw_mm_color = function ($label, $kind = 'bg') {
	if (function_exists('sc_color_field_compact')) {
		return sc_color_field_compact(array('label' => $label, 'kind' => $kind));
	}
	return array('type' => 'color-picker', 'label' => $label, 'value' => '');
};

// Each related cluster is its own border-less group, so the modal reads as
// distinct sections (Icon, Image, Subtitle, Badge, CSS Class). Leaf option ids
// are unchanged, so saved values round-trip with no migration.
$options = array_merge(
	// Icon + Icon Position (replaces the old standalone "Edit Icon" control).
	fw_ext_mega_menu_icon_options(),

	fw_ext_mega_menu_group('group_image', array(
		'item_image' => array(
			'type'  => 'upload',
			'label' => __('Item Image', 'fw'),
			'desc'  => __('Optional thumbnail shown beside the link (product / feature nav style).', 'fw'),
			'value' => '',
		),
	)),

	fw_ext_mega_menu_group('group_subtitle', array(
		'item_subtitle' => array(
			'type'  => 'text',
			'label' => __('Item Subtitle', 'fw'),
			'desc'  => __('Optional secondary line shown beneath the link label.', 'fw'),
			'value' => '',
		),
	)),

	fw_ext_mega_menu_group('group_badge', array(
		'badge_text' => array(
			'type'  => 'text',
			'label' => __('Badge Text', 'fw'),
			'desc'  => __('Small label shown next to the link, e.g. "New" or "Hot". Leave empty for none.', 'fw'),
			'value' => '',
		),
		'badge_color' => $fw_mm_color(__('Badge Color', 'fw'), 'bg'),
	)),

	fw_ext_mega_menu_group('group_extra_class', array(
		'extra_class' => array(
			'type'  => 'text',
			'label' => __('Extra CSS Class', 'fw'),
			'desc'  => __('Added to this item.', 'fw'),
			'value' => '',
		),
	))
);
