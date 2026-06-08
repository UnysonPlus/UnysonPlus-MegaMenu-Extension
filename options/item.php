<?php if (!defined('FW')) die('Forbidden');

// MegaMenu item options, column child, level 3+

$options = array(
	'badge_text' => array(
		'type'  => 'text',
		'label' => __('Badge Text', 'fw'),
		'desc'  => __('Small label shown next to the link, e.g. "New" or "Hot". Leave empty for none.', 'fw'),
		'value' => '',
	),
	'badge_color' => array(
		'type'  => 'color-picker',
		'label' => __('Badge Color', 'fw'),
		'value' => '',
	),
	'extra_class' => array(
		'type'  => 'text',
		'label' => __('Extra CSS Class', 'fw'),
		'desc'  => __('Added to this item.', 'fw'),
		'value' => '',
	),
);
