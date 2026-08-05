<?php if (!defined('FW')) die('Forbidden');
/**
 * @var WP_Post $item
 * @var string $title
 * @var array $attributes
 * @var object $args
 * @var int $depth
 */

// Icon — from the per-item "Settings" option (icon-v2), or the legacy Edit-Icon
// meta as a fallback. Rendered as an element INSIDE the link (so the icon font
// never clobbers the link text). Position (left / right / stacked-left) comes
// from the sibling Icon Position option.
$mm_icon_html = fw_ext_mega_menu_render_icon($item);
$mm_icon_pos  = ($mm_icon_html !== '')
	? (string) fw_ext_mega_menu_get_item_option($item, fw_ext_mega_menu_item_type($item), 'icon_position', 'left')
	: 'left';

// Per-item image (thumbnail) + subtitle (secondary text) — item-level options.
$mm_item_media = '';
$mm_item_subtitle = '';
if ($depth > 0) {
	$mm_img = fw_ext_mega_menu_get_item_option($item, 'item', 'item_image', '');
	$mm_img_url = is_array($mm_img) ? (isset($mm_img['url']) ? $mm_img['url'] : '') : $mm_img;
	if ($mm_img_url) {
		$mm_item_media = '<img class="mega-menu-item-img" src="' . esc_url($mm_img_url) . '" alt="" />';
	}

	$mm_sub = trim((string) fw_ext_mega_menu_get_item_option($item, 'item', 'item_subtitle', ''));
	if ($mm_sub !== '') {
		$mm_item_subtitle = '<span class="mega-menu-subtitle">' . esc_html($mm_sub) . '</span>';
	}
}

// Item badge — inline with the title (above any subtitle).
$mm_badge_html = '';
if ($depth > 0 && ($badge = trim((string) fw_ext_mega_menu_get_item_option($item, 'item', 'badge_text', ''))) !== '') {
	$badge_color = fw_ext_mega_menu_color_to_css(fw_ext_mega_menu_get_item_option($item, 'item', 'badge_color', ''), '');
	$badge_style = $badge_color !== '' ? ' style="background-color:' . esc_attr($badge_color) . '"' : '';
	$mm_badge_html = ' <span class="mega-menu-badge"' . $badge_style . '>' . esc_html($badge) . '</span>';
}

// A thumbnail image, or a stacked icon, takes the "media" slot beside the text.
// If both a thumbnail and a stacked icon are set, the thumbnail wins the slot and
// the icon falls back to inline (left for stacked-left, right for stacked-right).
$mm_effective_pos = $mm_icon_pos;
if ($mm_item_media !== '') {
	if ($mm_icon_pos === 'stacked-left')  { $mm_effective_pos = 'left'; }
	if ($mm_icon_pos === 'stacked-right') { $mm_effective_pos = 'right'; }
}

$mm_title_line = $args->link_before . $title . $args->link_after . $mm_badge_html;
if ($mm_icon_html !== '' && $mm_effective_pos === 'right') {
	$mm_title_line = $mm_title_line . $mm_icon_html;
} elseif ($mm_icon_html !== '' && $mm_effective_pos === 'left') {
	$mm_title_line = $mm_icon_html . $mm_title_line;
}
$mm_body_html = $mm_title_line . $mm_item_subtitle;

// A boxed (stacked) icon that isn't displaced by a thumbnail.
$mm_iconbox = '';
if ($mm_item_media === '' && $mm_icon_html !== '' && ($mm_icon_pos === 'stacked-left' || $mm_icon_pos === 'stacked-right')) {
	$mm_iconbox = '<span class="mega-menu-item-iconbox">' . $mm_icon_html . '</span>';
}

if ($mm_item_media !== '' || $mm_iconbox !== '') {
	// Media layout: icon/thumbnail beside a text body. Flag the <a> so CSS flexes it.
	$attributes['class'] = trim((isset($attributes['class']) ? $attributes['class'] : '') . ' mega-menu-item--media');
	$mm_body = '<span class="mega-menu-item-body">' . $mm_body_html . '</span>';
	if ($mm_item_media !== '') {
		$mm_link_inner = $mm_item_media . $mm_body; // thumbnail always on the left
	} elseif ($mm_icon_pos === 'stacked-right') {
		$attributes['class'] = trim($attributes['class'] . ' mm-iconbox-right');
		$mm_link_inner = $mm_body . $mm_iconbox; // boxed icon on the right
	} else {
		$mm_link_inner = $mm_iconbox . $mm_body; // boxed icon on the left
	}
} else {
	if ($mm_icon_html !== '' && $mm_effective_pos === 'right') {
		$attributes['class'] = trim((isset($attributes['class']) ? $attributes['class'] : '') . ' mm-ipos-right');
	}
	$mm_link_inner = $mm_body_html;
}

echo $args->before;
echo fw_html_tag('a', $attributes, $mm_link_inner);
echo $args->after;
