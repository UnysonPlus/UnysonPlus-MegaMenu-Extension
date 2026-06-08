<?php if (!defined('FW')) die('Forbidden');

// MegaMenu column options (depth 1)

$options = array(
	'width' => array(
		'type'    => 'select',
		'label'   => __('Column Width', 'fw'),
		'desc'    => __('Width of this column inside its row.', 'fw'),
		'value'   => 'auto',
		'choices' => array(
			'auto' => __('Auto (equal)', 'fw'),
			'1/2'  => __('1/2', 'fw'),
			'1/3'  => __('1/3', 'fw'),
			'2/3'  => __('2/3', 'fw'),
			'1/4'  => __('1/4', 'fw'),
			'3/4'  => __('3/4', 'fw'),
			'1/5'  => __('1/5', 'fw'),
			'1/6'  => __('1/6', 'fw'),
		),
	),
	'align' => array(
		'type'    => 'select',
		'label'   => __('Text Alignment', 'fw'),
		'value'   => '',
		'choices' => array(
			''       => __('Default', 'fw'),
			'left'   => __('Left', 'fw'),
			'center' => __('Center', 'fw'),
			'right'  => __('Right', 'fw'),
		),
	),
	'bg_color' => array(
		'type'  => 'color-picker',
		'label' => __('Background Color', 'fw'),
		'value' => '',
	),
	'extra_class' => array(
		'type'  => 'text',
		'label' => __('Extra CSS Class', 'fw'),
		'desc'  => __('Added to this column.', 'fw'),
		'value' => '',
	),
);
