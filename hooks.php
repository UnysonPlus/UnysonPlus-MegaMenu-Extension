<?php if (!defined('FW')) die('Forbidden');

/**
 * @param array $args
 * @return array
 * @internal
 */
function _filter_fw_ext_mega_menu_wp_nav_menu_args($args) {
	// nav-menu-template.php L271
	// $args['menu'] = ...

	// nav-menu-template.php L363
	// $args['menu_id'] = 'xxx-menu-id';
	// $args['menu_class'] = 'xxx-menu-class';

	// nav-menu-template.php L311
	// $args['container'] = 'xxx-container'; // should be in apply_filters('wp_nav_menu_container_allowedtags')
	// $args['container_id'] = 'xxx-container-id';
	// $args['container_class'] = 'xxx-container-class';

	// nav-menu-template.php L151
	// $args['before'] = 'xxx-before';
	// $args['after'] = 'xxx-after';
	// $args['link_before'] = 'xxx-link-before';
	// $args['link_after'] = 'xxx-link-after';

	// nav-menu-template.php L405
	// $args['items_wrap'] = '<ul id="%1$s" class="%2$s">%3$s</ul>';

	$args['walker'] = new FW_Ext_Mega_Menu_Walker();

	return $args;
}
add_filter('wp_nav_menu_args', '_filter_fw_ext_mega_menu_wp_nav_menu_args');

/**
 * Just for removing FW_Ext_Mega_Menu_Walker set in the previous
 * filter when the fallback menu is in action.
 * @param array $args
 * @return array
 * @internal
 */
function _filter_fw_ext_mega_menu_wp_page_menu_args($args) {
	if ($args['walker'] instanceof FW_Ext_Mega_Menu_Walker) {
		$args['walker'] = '';
	}

	return $args;
}
add_filter('wp_page_menu_args', '_filter_fw_ext_mega_menu_wp_page_menu_args');

/**
 * @param [WP_Post] $sorted_menu_items
 * @param $args
 * @return array
 * @internal
 */
function _filter_fw_ext_mega_menu_wp_nav_menu_objects($sorted_menu_items, $args) {
	// <li id="menu-item-1234" class="menu-item menu-item-type-post_type ... mega-menu">
	//     ....
	// </li>

	$mega_menu = array();
	foreach ($sorted_menu_items as $item) {
		if ($item->menu_item_parent == 0 && fw_ext_mega_menu_get_meta($item, 'enabled')) {
			$mega_menu[$item->ID] = true;
		}
	}

	foreach ($sorted_menu_items as $item) {
		if (isset($mega_menu[$item->ID])) {
			$item->classes[] = 'menu-item-has-mega-menu';
		}
		if (isset($mega_menu[$item->menu_item_parent])) {
			$item->classes[] = 'mega-menu-col';
		}
		if (fw_ext_mega_menu_get_meta($item, 'icon')) {
			$item->classes[] = 'menu-item-has-icon';
		}

		// Per-item "Settings" options: width/alignment/extra classes.
		// Levels: 1 = row, 2 = column, 3+ = item, 0 = default (non-MegaMenu).
		$extra_class = '';
		switch (fw_ext_mega_menu_is_mm_item($item)) {
			case 2: // column
				$width = (string) fw_ext_mega_menu_get_item_option($item, 'column', 'width', 'auto');
				$item->classes[] = 'mm-col-' . str_replace('/', '-', $width);

				if ($align = fw_ext_mega_menu_get_item_option($item, 'column', 'align', '')) {
					$item->classes[] = 'mm-col-align-' . sanitize_html_class($align);
				}

				$extra_class = (string) fw_ext_mega_menu_get_item_option($item, 'column', 'extra_class', '');
				break;
			case 1: // row: extra class goes on the .mega-menu container, not the <li>
				break;
			case 0: // default (non-MegaMenu) item
				$extra_class = (string) fw_ext_mega_menu_get_item_option($item, 'default', 'extra_class', '');
				break;
			default: // 3+ item
				$extra_class = (string) fw_ext_mega_menu_get_item_option($item, 'item', 'extra_class', '');
		}

		if ($extra_class !== '') {
			foreach (preg_split('/\s+/', trim($extra_class)) as $cls) {
				if ($cls !== '') {
					$item->classes[] = sanitize_html_class($cls);
				}
			}
		}
	}

	return $sorted_menu_items;
}
add_filter('wp_nav_menu_objects', '_filter_fw_ext_mega_menu_wp_nav_menu_objects', 10, 2);

/**
 * nav-menu-template.php L174
 * Walker_Nav_Menu::start_el
 *
 * @param $item_output
 * @param $item
 * @param $depth
 * @param $args
 * @return string
 * @internal
 */
function _filter_fw_ext_mega_menu_walker_nav_menu_start_el($item_output, $item, $depth, $args) {
	/** @since 1.1.3 */
	if (apply_filters('fw:ext:megamenu:start_el_item_content:disable', false, $item)) {
		return $item_output;
	}

	if (!fw_ext_mega_menu_is_mm_item($item)) {
		return $item_output;
	}

	// <li>
	//     {{ item_output }}
	//     <div>{{ item.description }}</div>
	//     <div class="mega-menu">
	//         <ul class="sub-menu"></ul>
	//     </div>
	// </li>

	if ($depth > 0 && fw_ext_mega_menu_get_meta($item, 'title-off')) {
		$item_output = '';
	}

	// Note that raw description is stored in post_content field.
	$post_content = (string) $item->post_content;
	if ($depth > 0 && trim($post_content) !== '') {
		$item_output .= '<div>' . do_shortcode($post_content) . '</div>';
	}

	return $item_output;
}
add_filter('walker_nav_menu_start_el', '_filter_fw_ext_mega_menu_walker_nav_menu_start_el', 10, 4);
