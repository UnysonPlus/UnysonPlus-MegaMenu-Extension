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
		if (fw_ext_mega_menu_item_icon($item)) {
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

		// Per-device visibility → mm-hide-{device} classes on the <li> (works for any type:
		// a whole mega item, a column, or a single link). Styled by media queries in frontend.css.
		$mm_hide = fw_ext_mega_menu_get_item_option($item, fw_ext_mega_menu_item_type($item), 'hide_on', array());
		if (is_array($mm_hide)) {
			foreach (array('desktop', 'tablet', 'mobile') as $mm_dev) {
				if (!empty($mm_hide[$mm_dev])) {
					$item->classes[] = 'mm-hide-' . $mm_dev;
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
	/**
	 * Filters whether to skip the mega-menu walker's custom start-element rendering for a given menu item.
	 *
	 * @since 1.1.3
	 */
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
		// The description rides INSIDE the item's own <a> (a block link: label over description) so the
		// theme's dropdown-link padding boxes label + description together. As a sibling <div> after the
		// anchor it sat flush left while the label carried the link's 1rem inset — the two lines never
		// aligned, and the item had no hit area over its description.
		$desc_html = '<span class="mega-menu-desc">' . do_shortcode($post_content) . '</span>';
		$a_close   = strrpos($item_output, '</a>');
		if ($a_close !== false) {
			$label = substr($item_output, 0, $a_close);
			// wrap the bare label text in a span so the two lines stack cleanly
			if (preg_match('/^(.*<a[\s>][^>]*>|.*<a>)(.*)$/s', $label, $lm)) { $label = $lm[1] . '<span class="mega-menu-label">' . $lm[2] . '</span>'; }
			$item_output = $label . $desc_html . substr($item_output, $a_close);
		} else {
			$item_output .= '<div class="mega-menu-desc">' . do_shortcode($post_content) . '</div>';
		}
	}

	// Column content types (image / rich content / widget / raw). A column is a
	// level-2 MegaMenu item; when its Content is not "links", render the payload
	// here (in place of the — usually empty — sub-menu).
	if (fw_ext_mega_menu_is_mm_item($item) == 2) {
		$content_type = (string) fw_ext_mega_menu_get_item_option($item, 'column', 'content_type', 'links');
		if ($content_type !== 'links') {
			$item_output .= fw_ext_mega_menu_render_column_content($item, $content_type);
		}
	}

	return $item_output;
}
add_filter('walker_nav_menu_start_el', '_filter_fw_ext_mega_menu_walker_nav_menu_start_el', 10, 4);

/**
 * Late fallback for conditional asset loading: if a mega menu is rendered on a page
 * where the location scan (fw_ext_mega_menu_should_enqueue_assets) didn't detect it
 * — e.g. a wp_nav_menu() call with an explicit menu, or a menu widget — enqueue the
 * assets now. fw_ext_mega_menu_do_enqueue() is idempotent, so this never double-loads.
 * Late styles print in the footer (a minor, edge-case trade-off vs loading on every page).
 *
 * @internal
 */
function _filter_fw_ext_mega_menu_late_enqueue($nav_menu, $args) {
	if (!is_admin() && is_string($nav_menu) && strpos($nav_menu, 'menu-item-has-mega-menu') !== false) {
		fw_ext_mega_menu_do_enqueue();
	}
	return $nav_menu;
}
add_filter('wp_nav_menu', '_filter_fw_ext_mega_menu_late_enqueue', 10, 2);

/**
 * Block-editor / FSE bridge
 * -------------------------
 * A server-rendered "Mega Menu" block that outputs a chosen nav menu THROUGH the mega walker, so a
 * mega menu can be placed in the Site Editor / block editor / any block area — not only the classic
 * theme header. wp_nav_menu() triggers the walker and the conditional-asset fallback, so the block
 * gets the full mega behaviour + CSS/JS with no extra wiring.
 */
function fw_ext_mega_menu_register_block() {
	if (!function_exists('register_block_type')) {
		return; // WP < 5.0
	}
	$ext = fw_ext('megamenu');
	if (!$ext) {
		return;
	}

	wp_register_script(
		'fw-ext-megamenu-block',
		$ext->get_uri('/static/js/block.js'),
		array('wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n'),
		$ext->manifest->get_version(),
		true
	);

	// Feed the editor the list of nav menus to choose from (no async fetch needed).
	$menus = array();
	foreach ((array) wp_get_nav_menus() as $m) {
		$menus[] = array('id' => (int) $m->term_id, 'name' => $m->name);
	}
	wp_localize_script('fw-ext-megamenu-block', '_fw_mm_block', array(
		'menus' => $menus,
		'i18n'  => array(
			'title'   => __('Mega Menu', 'fw'),
			'desc'    => __('Display a navigation menu with Mega Menu support.', 'fw'),
			'menu'    => __('Menu', 'fw'),
			'pick'    => __('— Select a menu —', 'fw'),
			'empty'   => __('Choose a menu to display in the block settings.', 'fw'),
		),
	));

	register_block_type('unysonplus/mega-menu', array(
		'api_version'     => 2,
		'editor_script'   => 'fw-ext-megamenu-block',
		'attributes'      => array('menu' => array('type' => 'number', 'default' => 0)),
		'render_callback' => 'fw_ext_mega_menu_block_render',
	));
}
add_action('init', 'fw_ext_mega_menu_register_block');

/**
 * Server render for the Mega Menu block.
 * @internal
 */
function fw_ext_mega_menu_block_render($attributes) {
	$menu = isset($attributes['menu']) ? (int) $attributes['menu'] : 0;
	if (!$menu || !wp_get_nav_menu_object($menu)) {
		return current_user_can('edit_theme_options')
			? '<p class="unysonplus-mega-menu-block__placeholder">' . esc_html__('Mega Menu — choose a menu in the block settings.', 'fw') . '</p>'
			: '';
	}
	return wp_nav_menu(array(
		'menu'            => $menu,
		'echo'            => false,
		'container'       => 'nav',
		'container_class' => 'unysonplus-mega-menu-block',
		'menu_class'      => 'primary-menu',
		'fallback_cb'     => false,
	));
}

/**
 * Keep the classic Appearance → Menus screen (where mega menus are built) reachable everywhere,
 * including block / FSE themes that would otherwise hide it. `menus` support is benign.
 */
add_action('after_setup_theme', function () {
	add_theme_support('menus');
}, 20);
