<?php if (!defined('FW')) die('Forbidden');

if (is_admin()) {
	return;
}

if (!fw_ext('megamenu')) {
	return;
}

// Conditional loading: only enqueue the mega-menu CSS/JS when a menu assigned to a
// theme nav-menu LOCATION actually contains an enabled mega item. Menus rendered
// elsewhere (a raw wp_nav_menu with an explicit menu, or a widget) are caught by the
// late wp_nav_menu fallback in hooks.php. This keeps ~14KB of CSS/JS off every page
// that has no mega menu. Override with the 'fw:ext:megamenu:force-enqueue' filter.
if (fw_ext_mega_menu_should_enqueue_assets()) {
	fw_ext_mega_menu_do_enqueue();
}
