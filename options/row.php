<?php if (!defined('FW')) die('Forbidden');

// MegaMenu row options (the dropdown panel, depth 0)

$options = array(
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
	'bg_color' => array(
		'type'  => 'color-picker',
		'label' => __('Background Color', 'fw'),
		'value' => '',
	),
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
	'extra_class' => array(
		'type'  => 'text',
		'label' => __('Extra CSS Class', 'fw'),
		'desc'  => __('Added to the mega menu dropdown container.', 'fw'),
		'value' => '',
	),
);
