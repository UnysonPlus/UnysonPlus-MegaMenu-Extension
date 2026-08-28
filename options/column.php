<?php if (!defined('FW')) die('Forbidden');

// MegaMenu column options (depth 1)

// Palette-preset compact color control (project convention); raw color-picker
// fallback when the shortcodes helper isn't active.
$fw_mm_color = function ($label, $kind = 'bg') {
	if (function_exists('sc_color_field_compact')) {
		return sc_color_field_compact(array('label' => $label, 'kind' => $kind));
	}
	return array('type' => 'color-picker', 'label' => $label, 'value' => '');
};

// Registered widget areas (sidebars) → choices for the "Widget area" content type.
$fw_mm_widget_areas = array('' => __('— Select a widget area —', 'fw'));
if (!empty($GLOBALS['wp_registered_sidebars']) && is_array($GLOBALS['wp_registered_sidebars'])) {
	foreach ($GLOBALS['wp_registered_sidebars'] as $fw_mm_sb_id => $fw_mm_sb) {
		$fw_mm_widget_areas[$fw_mm_sb_id] = isset($fw_mm_sb['name']) ? $fw_mm_sb['name'] : $fw_mm_sb_id;
	}
}

// Visual width tiles for the "Column Width" image-picker (like the page builder's
// Column "Width Override", but without the per-device wrapper — a mega panel just
// stacks on mobile). Each tile is an inline data-URI SVG: a mini row bar with the
// column's fraction filled. Value keys are unchanged (auto, 1/2, …) so switching
// from the old select to this picker needs no data migration.
$fw_mm_width_svg = function ($fill, $label) {
	$w = 116; $h = 50; $accent = function_exists( 'fw_upw_icon_palette' ) ? fw_upw_icon_palette()['accent'] : '#3858e9'; $grey = function_exists( 'fw_upw_icon_palette' ) ? fw_upw_icon_palette()['structure'] : '#dadada';
	$bx = 10; $by = 12; $bw = 96; $bh = 15; $rx = 3;
	$outline = '<rect x="' . $bx . '" y="' . $by . '" width="' . $bw . '" height="' . $bh . '" rx="' . $rx . '" fill="none" stroke="' . $grey . '" stroke-width="1.4"/>';
	if ($fill === 'auto') {
		$seg = ($bw - 2 * 4) / 3;
		$fillrect = '';
		for ($i = 0; $i < 3; $i++) {
			$x = $bx + $i * ($seg + 4);
			$fillrect .= '<rect x="' . round($x) . '" y="' . $by . '" width="' . round($seg) . '" height="' . $bh . '" rx="' . $rx . '" fill="' . $accent . '"/>';
		}
	} else {
		$fillrect = '<rect x="' . $bx . '" y="' . $by . '" width="' . round($bw * $fill) . '" height="' . $bh . '" rx="' . $rx . '" fill="' . $accent . '"/>';
	}
	$text = '<text x="' . ($w / 2) . '" y="' . ($h - 6) . '" text-anchor="middle" font-family="-apple-system,Segoe UI,Roboto,sans-serif" font-size="11" fill="#50575e">' . $label . '</text>';
	$svg  = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $w . ' ' . $h . '" width="' . $w . '" height="' . $h . '">' . $outline . $fillrect . $text . '</svg>';
	return 'data:image/svg+xml,' . rawurlencode($svg);
};
$fw_mm_width_tile = function ($fill, $label) use ($fw_mm_width_svg) {
	$uri = $fw_mm_width_svg($fill, $label);
	return array('small' => array('height' => 50, 'src' => $uri), 'large' => array('height' => 70, 'src' => $uri));
};

// Per-cluster border-less groups (Icon, Width, Background, Column Content, CSS Class).
$options = array_merge(
	fw_ext_mega_menu_icon_options(),

	fw_ext_mega_menu_group('group_width', array(
		'width' => array(
			'type'    => 'image-picker',
			'label'   => __('Column Width', 'fw'),
			'desc'    => __('Width of this column inside its row. "Auto" makes all columns share the row equally.', 'fw'),
			'value'   => 'auto',
			'choices' => array(
				'auto' => $fw_mm_width_tile('auto', __('Auto', 'fw')),
				'1/2'  => $fw_mm_width_tile(1 / 2, '1/2'),
				'1/3'  => $fw_mm_width_tile(1 / 3, '1/3'),
				'2/3'  => $fw_mm_width_tile(2 / 3, '2/3'),
				'1/4'  => $fw_mm_width_tile(1 / 4, '1/4'),
				'3/4'  => $fw_mm_width_tile(3 / 4, '3/4'),
				'1/5'  => $fw_mm_width_tile(1 / 5, '1/5'),
				'1/6'  => $fw_mm_width_tile(1 / 6, '1/6'),
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
		'bg_size' => array(
			'type'    => 'select',
			'label'   => __('Background Size', 'fw'),
			'value'   => 'auto',
			'choices' => array(
				'auto'    => __('Auto', 'fw'),
				'cover'   => __('Cover', 'fw'),
				'contain' => __('Contain', 'fw'),
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

	// --- Column content type ------------------------------------------------
	// By default a column shows its sub-menu links. Switch it to hold an image,
	// rich content (HTML + shortcodes), a widget area, or raw HTML — the columns
	// that make this a true "mega" menu rather than a grid of links. The chosen
	// content renders in place of the sub-menu (add it as a column with no
	// child items). See fw_ext_mega_menu_render_column_content() in helpers.php.
	fw_ext_mega_menu_group('group_content', array(
		'content_type' => array(
			'type'    => 'select',
			'label'   => __('Column Content', 'fw'),
			'desc'    => __('What this column holds. "Menu links" shows its sub-items (default). Choose Image, Rich content, Widget area, or Raw HTML to make it a content column instead — add it with no child menu items.', 'fw'),
			'value'   => 'links',
			'choices' => array(
				'links'   => __('Menu links (default)', 'fw'),
				'image'   => __('Image', 'fw'),
				'content' => __('Rich content (HTML + shortcodes)', 'fw'),
				'widget'  => __('Widget area', 'fw'),
				'raw'     => __('Raw HTML', 'fw'),
			),
		),
		'content_image' => array(
			'type'    => 'upload',
			'label'   => __('Image', 'fw'),
			'value'   => '',
			'show_if' => array('content_type' => 'image'),
		),
		'content_image_link' => array(
			'type'    => 'text',
			'label'   => __('Image Link URL', 'fw'),
			'desc'    => __('Optional. Wraps the image in a link (external links open in a new tab).', 'fw'),
			'value'   => '',
			'show_if' => array('content_type' => 'image'),
		),
		'content_image_alt' => array(
			'type'    => 'text',
			'label'   => __('Image Alt Text', 'fw'),
			'value'   => '',
			'show_if' => array('content_type' => 'image'),
		),
		'content_html' => array(
			'type'    => 'textarea',
			'label'   => __('Rich Content', 'fw'),
			'desc'    => __('HTML and shortcodes are allowed. Rendered inside the column.', 'fw'),
			'value'   => '',
			'show_if' => array('content_type' => 'content'),
		),
		'content_widget_area' => array(
			'type'    => 'select',
			'label'   => __('Widget Area', 'fw'),
			'desc'    => __('The widget area (sidebar) to output in this column.', 'fw'),
			'value'   => '',
			'choices' => $fw_mm_widget_areas,
			'show_if' => array('content_type' => 'widget'),
		),
		'content_raw' => array(
			'type'    => 'textarea',
			'label'   => __('Raw HTML', 'fw'),
			'desc'    => __('Output exactly as entered (shortcodes still run). For trusted markup only.', 'fw'),
			'value'   => '',
			'show_if' => array('content_type' => 'raw'),
		),
	)),

	fw_ext_mega_menu_group('group_extra_class', array(
		'extra_class' => array(
			'type'  => 'text',
			'label' => __('Extra CSS Class', 'fw'),
			'desc'  => __('Added to this column.', 'fw'),
			'value' => '',
		),
	))
);
