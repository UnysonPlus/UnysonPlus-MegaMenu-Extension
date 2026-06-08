<?php if (!defined('FW')) die('Forbidden');
/**
 * @var WP_Post $item
 * @var string $title
 * @var array $attributes
 * @var object $args
 * @var int $depth
 */

if (
	fw()->extensions->get('megamenu')->show_icon()
	&&
	($icon = fw_ext_mega_menu_get_meta($item, 'icon'))
) {
	/**
	 * Back-compat shim: a bare Font Awesome 4 token like "fa-star" (no style prefix)
	 * won't render under Font Awesome 6. Promote it to a solid style. Values that
	 * already carry a style prefix (e.g. "fa fa-star", "fas fa-star", "fa-solid fa-star")
	 * are left untouched.
	 */
	if (strpos($icon, ' ') === false && strpos($icon, 'fa-') === 0) {
		$icon = 'fa-solid ' . $icon;
	}

	if (empty($attributes['class'])) {
		$attributes['class'] = $icon;
	} else {
		$attributes['class'] .= ' ' . $icon;
	}
}

echo $args->before;
echo fw_html_tag('a', $attributes, $args->link_before . $title . $args->link_after);

// Item badge (per-item "Settings" option), only inside a MegaMenu column
if ($depth > 0 && ($badge = trim((string) fw_ext_mega_menu_get_item_option($item, 'item', 'badge_text', '')))) {
	$badge_color = fw_ext_mega_menu_get_item_option($item, 'item', 'badge_color', '');
	$badge_style = $badge_color ? ' style="background-color:' . esc_attr($badge_color) . '"' : '';
	echo ' <span class="mega-menu-badge"' . $badge_style . '>' . esc_html($badge) . '</span>';
}

echo $args->after;
