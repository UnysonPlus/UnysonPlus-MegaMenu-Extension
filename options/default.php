<?php if (!defined('FW')) die('Forbidden');

// default (not MegaMenu) item options

// Per-cluster border-less groups (Icon, CSS Class).
$options = array_merge(
	fw_ext_mega_menu_icon_options(),
	fw_ext_mega_menu_group('group_extra_class', array(
		'extra_class' => array(
			'type'  => 'text',
			'label' => __('Extra CSS Class', 'fw'),
			'desc'  => __('Added to this menu item.', 'fw'),
			'value' => '',
		),
	))
);
