<?php if (!defined('FW')) die('Forbidden');

// MegaMenu row options (the dropdown panel, depth 0)

// Palette-preset compact color control (per the project convention). Falls back
// to a raw color-picker when the shortcodes extension (helper) isn't active.
$fw_mm_color = function ($label, $kind = 'bg') {
	if (function_exists('sc_color_field_compact')) {
		return sc_color_field_compact(array('label' => $label, 'kind' => $kind));
	}
	return array('type' => 'color-picker', 'label' => $label, 'value' => '');
};

// Per-cluster border-less groups (Icon, Dropdown Width, Background, CSS Class).
$options = array_merge(
	fw_ext_mega_menu_icon_options(),

	fw_ext_mega_menu_group('group_dropdown', array(
		'dropdown_width' => array(
			'type'    => 'select',
			'label'   => __('Dropdown Width', 'fw'),
			'desc'    => __('Width of the mega menu dropdown panel.', 'fw'),
			'value'   => 'default',
			'choices' => array(
				'default'    => __('Default (theme)', 'fw'),
				'full-width' => __('Full width (viewport)', 'fw'),
				'custom'     => __('Custom', 'fw'),
			),
		),
		'dropdown_custom_width' => array(
			'type'    => 'text',
			'label'   => __('Custom Width', 'fw'),
			'desc'    => __('e.g. 800px or 90%. Used when "Dropdown Width" is "Custom".', 'fw'),
			'value'   => '',
			'show_if' => array('dropdown_width' => 'custom'),
		),
	)),

	fw_ext_mega_menu_group('group_background', array(
		'bg_color' => $fw_mm_color(__('Background Color', 'fw'), 'bg'),
		'bg_image' => array(
			'type'  => 'upload',
			'label' => __('Background Image', 'fw'),
			'value' => '',
		),
		'bg_position' => array(
			'type'    => 'select',
			'label'   => __('Background Position', 'fw'),
			'value'   => 'center center',
			'choices' => array(
				'left top'      => __('Left top', 'fw'),
				'center top'    => __('Center top', 'fw'),
				'right top'     => __('Right top', 'fw'),
				'left center'   => __('Left center', 'fw'),
				'center center' => __('Center center', 'fw'),
				'right center'  => __('Right center', 'fw'),
				'left bottom'   => __('Left bottom', 'fw'),
				'center bottom' => __('Center bottom', 'fw'),
				'right bottom'  => __('Right bottom', 'fw'),
			),
		),
		'bg_repeat' => array(
			'type'    => 'select',
			'label'   => __('Background Repeat', 'fw'),
			'value'   => 'no-repeat',
			'choices' => array(
				'no-repeat' => __('No repeat', 'fw'),
				'repeat'    => __('Repeat', 'fw'),
				'repeat-x'  => __('Repeat X', 'fw'),
				'repeat-y'  => __('Repeat Y', 'fw'),
			),
		),
	)),

	fw_ext_mega_menu_group('group_extra_class', array(
		'extra_class' => array(
			'type'  => 'text',
			'label' => __('Extra CSS Class', 'fw'),
			'desc'  => __('Added to the mega menu dropdown container.', 'fw'),
			'value' => '',
		),
	))
);
