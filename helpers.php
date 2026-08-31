<?php if (!defined('FW')) die('Forbidden');

/**
 * Returns a mega menu meta value for a post, falling back to the given default.
 *
 * @param int|object $post
 * @param $key
 * @param null $default
 * @return mixed
 */
/** Returns a mega menu meta value for a post, falling back to the given default. */
function fw_ext_mega_menu_get_meta($post, $key, $default = null) {
	return _fw_ext_mega_menu_meta($post, $key, $default);
}

/** Updates mega menu meta for a post from the given key-value array. */
function fw_ext_mega_menu_update_meta($post, array $array) {
	return _fw_ext_mega_menu_meta($post, $array, null, true);
}

/**
 * Read a single per-item option value (defined in options/{row,column,item,default}.php)
 * saved through the "Settings" modal (FW_Db_Options_Model_MegaMenu).
 *
 * @param int|WP_Post $item
 * @param string $type       One of 'row' | 'column' | 'item' | 'default'
 * @param string|null $option_id Leaf option id, or null for the whole type group
 * @param mixed $default
 * @return mixed
 */
function fw_ext_mega_menu_get_item_option($item, $type, $option_id = null, $default = null) {
	$id = is_object($item) ? $item->ID : intval($item);

	if (!$id || !function_exists('fw_ext_mega_menu_get_db_item_option')) {
		return $default;
	}

	$key = $type . ($option_id !== null ? '/' . $option_id : '');

	return fw_ext_mega_menu_get_db_item_option($id, $key, $default);
}

/**
 * Build the inline style string for a MegaMenu row (dropdown panel) container.
 *
 * @param int $row_id Top-level MegaMenu item id
 * @return string Escaped CSS, or '' when nothing to apply
 */
function fw_ext_mega_menu_row_container_style($row_id) {
	$css = array();

	if (fw_ext_mega_menu_get_item_option($row_id, 'row', 'dropdown_width', 'default') === 'custom') {
		$custom = trim((string) fw_ext_mega_menu_get_item_option($row_id, 'row', 'dropdown_custom_width', ''));
		if ($custom !== '') {
			$css[] = 'width:' . esc_attr($custom);
		}
	}

	$bg_color = fw_ext_mega_menu_color_to_css(fw_ext_mega_menu_get_item_option($row_id, 'row', 'bg_color', ''), '');
	if ($bg_color !== '') {
		$css[] = 'background-color:' . esc_attr($bg_color);
	}

	$bg_image = fw_ext_mega_menu_get_item_option($row_id, 'row', 'bg_image', '');
	$bg_url   = is_array($bg_image) ? (isset($bg_image['url']) ? $bg_image['url'] : '') : $bg_image;
	if ($bg_url) {
		$css[] = 'background-image:url(' . esc_url($bg_url) . ')';
		$css[] = 'background-position:' . esc_attr(fw_ext_mega_menu_get_item_option($row_id, 'row', 'bg_position', 'center center'));
		$css[] = 'background-repeat:' . esc_attr(fw_ext_mega_menu_get_item_option($row_id, 'row', 'bg_repeat', 'no-repeat'));
	}

	return implode(';', $css);
}

/**
 * The per-item options type for a menu item, by its MegaMenu level:
 * 1 = row (top trigger), 2 = column, 3+ = item, 0 = default (non-mega item).
 *
 * @param int|WP_Post $item
 * @return string one of 'row' | 'column' | 'item' | 'default'
 */
function fw_ext_mega_menu_item_type($item) {
	$level = fw_ext_mega_menu_is_mm_item($item);
	if ($level === 1) { return 'row'; }
	if ($level === 2) { return 'column'; }
	if ($level >= 3) { return 'item'; }
	return 'default';
}

/**
 * Is an icon value (icon-v2 array, or a legacy class string) actually set?
 *
 * @param mixed $val
 * @return bool
 */
function fw_ext_mega_menu_icon_is_set($val) {
	if (is_string($val)) {
		return trim($val) !== '';
	}
	if (!is_array($val)) {
		return false;
	}
	$type = isset($val['type']) ? $val['type'] : '';
	switch ($type) {
		case 'icon-font':     return !empty($val['icon-class']);
		case 'emoji':         return isset($val['char']) && $val['char'] !== '';
		case 'svg':           return !empty($val['markup']) || !empty($val['svg-id']) || !empty($val['url']);
		case 'custom-upload': return !empty($val['url']) || !empty($val['attachment-id']);
	}
	return false;
}

/**
 * The icon VALUE for a menu item — the modern per-item option (icon-v2) when
 * set, otherwise the legacy `mega-menu[id][icon]` class string saved by the old
 * standalone "Edit Icon" control. Returns null when there is no icon.
 *
 * The returned value is fed straight to sc_icon_render() (which accepts both the
 * icon-v2 array and a legacy string), so no per-type branching is needed here.
 *
 * @param int|WP_Post $item
 * @return array|string|null
 */
function fw_ext_mega_menu_item_icon($item) {
	$val = fw_ext_mega_menu_get_item_option($item, fw_ext_mega_menu_item_type($item), 'icon', null);
	if (fw_ext_mega_menu_icon_is_set($val)) {
		return $val;
	}
	$legacy = (string) fw_ext_mega_menu_get_meta($item, 'icon');
	return $legacy !== '' ? $legacy : null;
}

/**
 * Render a menu item's icon to HTML. Prefers the shortcodes extension's
 * sc_icon_render() (font / emoji / svg / image, FA4→FA6 normalized); falls back
 * to a plain font <i> for a class string when that extension is inactive.
 *
 * @param int|WP_Post $item
 * @param string      $extra_class extra class on the rendered icon
 * @return string
 */
function fw_ext_mega_menu_render_icon($item, $extra_class = '') {
	$val = fw_ext_mega_menu_item_icon($item);
	if ($val === null) {
		return '';
	}
	$class = trim('mega-menu-icon ' . $extra_class);

	if (function_exists('sc_icon_render')) {
		return sc_icon_render($val, array('class' => $class));
	}

	// Standalone fallback: only a font-icon class string is supported.
	$cls = is_string($val) ? $val : (isset($val['icon-class']) ? $val['icon-class'] : '');
	$cls = trim((string) $cls);
	if ($cls === '') {
		return '';
	}
	if (strpos($cls, ' ') === false && strpos($cls, 'fa-') === 0) {
		$cls = 'fa-solid ' . $cls;
	}
	return '<i class="' . esc_attr($class . ' ' . $cls) . '" aria-hidden="true"></i>';
}

/**
 * The shared Icon + Icon Position options, added to every per-item options set
 * (row / column / item / default) so the icon is edited inside the "Settings"
 * modal instead of the old standalone control.
 *
 * @return array
 */
function fw_ext_mega_menu_icon_options() {
	$icon = fw_ext('megamenu')->get_icon_option();
	$icon['label'] = __('Icon', 'fw');
	$icon['desc']  = __('Optional icon shown with the link.', 'fw');

	// Position preview tiles (inline data-URI SVG): a mini item row showing where
	// the icon sits relative to the text. Left / Right are inline with the title;
	// Stacked-left is a larger icon vertically centered against a title + subtitle.
	$pos_svg = function ($variant) {
		// Narrow viewBox (90x44) so all four tiles fit on a single row of the
		// float layout in the Settings modal, while staying visually distinct.
		$w = 90; $h = 44; $accent = function_exists( 'fw_upw_icon_palette' ) ? fw_upw_icon_palette()['accent'] : '#3858e9'; $grey = function_exists( 'fw_upw_icon_palette' ) ? fw_upw_icon_palette()['structure'] : '#dadada';
		$sq = function ($x, $y, $s) use ($accent) {
			return '<rect x="' . $x . '" y="' . $y . '" width="' . $s . '" height="' . $s . '" rx="3" fill="' . $accent . '"/>';
		};
		$ln = function ($x, $y, $lw, $hh) use ($grey) {
			return '<rect x="' . $x . '" y="' . $y . '" width="' . $lw . '" height="' . $hh . '" rx="2.5" fill="' . $grey . '"/>';
		};
		// Inline (left/right): a SMALL icon on the TITLE line, with the single
		// subtitle line BELOW it, full width. Stacked (left/right): a LARGER icon
		// vertically centred against the title + subtitle block beside it.
		if ($variant === 'left') {
			$body = $sq(9, 8, 10) . $ln(24, 9, 40, 5) . $ln(9, 25, 58, 4);
		} elseif ($variant === 'right') {
			$body = $ln(9, 9, 40, 5) . $sq(58, 8, 10) . $ln(9, 25, 58, 4);
		} elseif ($variant === 'stacked-left') {
			$body = $sq(9, 11, 19) . $ln(34, 13, 42, 5) . $ln(34, 24, 28, 4);
		} else { // stacked-right
			$body = $ln(14, 13, 42, 5) . $ln(14, 24, 28, 4) . $sq(61, 11, 19);
		}
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $w . ' ' . $h . '" width="' . $w . '" height="' . $h . '">' . $body . '</svg>';
		return 'data:image/svg+xml,' . rawurlencode($svg);
	};
	$pos_tile = function ($variant) use ($pos_svg) {
		$uri = $pos_svg($variant);
		return array('small' => array('height' => 44, 'src' => $uri), 'large' => array('height' => 58, 'src' => $uri));
	};

	return array(
		'group_icon' => array(
			'type'    => 'group',
			'options' => array(
				'icon' => $icon,
				'icon_position' => array(
					'type'    => 'image-picker',
					'label'   => __('Icon Position', 'fw'),
					'desc'    => __('Where the icon sits relative to the link text.', 'fw'),
					'value'   => 'left',
					'choices' => array(
						'left'          => $pos_tile('left'),
						'right'         => $pos_tile('right'),
						'stacked-left'  => $pos_tile('stacked-left'),
						'stacked-right' => $pos_tile('stacked-right'),
					),
				),
			),
		),
	);
}

/**
 * Wrap a set of options in a border-less group container (house style), keyed by
 * a distinct group id. Container-only — leaf ids and saved values are unchanged.
 *
 * @param string $group_id
 * @param array  $options
 * @return array single-entry array: { <group_id>: { type:group, options } }
 */
function fw_ext_mega_menu_group($group_id, array $options) {
	return array($group_id => array('type' => 'group', 'options' => $options));
}

/**
 * Per-device visibility control, shared by every item type (row / column / item /
 * default). The selected screen sizes become `mm-hide-{device}` classes on the
 * menu item (see hooks.php), styled by the media queries in frontend.css.
 *
 * @return array
 */
function fw_ext_mega_menu_visibility_options() {
	return fw_ext_mega_menu_group('group_visibility', array(
		'hide_on' => array(
			'type'    => 'checkboxes',
			'label'   => __('Hide On', 'fw'),
			'desc'    => __('Hide this from the menu on the selected screen sizes.', 'fw'),
			'value'   => array(),
			'choices' => array(
				'desktop' => __('Desktop (>= 992px)', 'fw'),
				'tablet'  => __('Tablet (768-991px)', 'fw'),
				'mobile'  => __('Mobile (< 768px)', 'fw'),
			),
		),
	));
}

/**
 * Resolve a color value to a CSS color string. Prefers the shortcodes
 * extension's sc_color_to_css() (compact preset picker → var(--color-{slug}) or
 * custom hex); falls back to a self-contained resolver when that extension is
 * inactive (values are then plain color-picker strings). Tolerates the legacy
 * plain-hex shape either way, so no data migration is needed.
 *
 * @param mixed  $value    string|array from a color-picker / compact preset field
 * @param string $fallback returned when nothing usable is set
 * @return string
 */
function fw_ext_mega_menu_color_to_css($value, $fallback = '') {
	if (function_exists('sc_color_to_css')) {
		return sc_color_to_css($value, $fallback);
	}
	if (is_string($value)) {
		return $value !== '' ? $value : $fallback;
	}
	if (is_array($value)) {
		if (!empty($value['predefined'])) {
			$slug = preg_replace('/^(?:text|bg)-/', '', (string) $value['predefined']);
			$slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($slug));
			return $slug !== '' ? 'var(--color-' . $slug . ')' : $fallback;
		}
		return !empty($value['custom']) ? (string) $value['custom'] : $fallback;
	}
	return $fallback;
}

/**
 * Build the inline style for a MegaMenu column <li> (background color + image).
 * Per-part escaped, concatenated raw (matches the row helper's usage).
 *
 * @param int $column_id
 * @return string  '' when nothing to apply
 */
function fw_ext_mega_menu_column_style($column_id) {
	$css = array();

	$bg = fw_ext_mega_menu_color_to_css(fw_ext_mega_menu_get_item_option($column_id, 'column', 'bg_color', ''), '');
	if ($bg !== '') {
		$css[] = 'background-color:' . esc_attr($bg);
	}

	$img = fw_ext_mega_menu_get_item_option($column_id, 'column', 'bg_image', '');
	$url = is_array($img) ? (isset($img['url']) ? $img['url'] : '') : $img;
	if ($url) {
		$css[] = 'background-image:url(' . esc_url($url) . ')';
		$css[] = 'background-position:' . esc_attr(fw_ext_mega_menu_get_item_option($column_id, 'column', 'bg_position', 'center center'));
		$css[] = 'background-size:' . esc_attr(fw_ext_mega_menu_get_item_option($column_id, 'column', 'bg_size', 'auto'));
		$css[] = 'background-repeat:' . esc_attr(fw_ext_mega_menu_get_item_option($column_id, 'column', 'bg_repeat', 'no-repeat'));
	}

	return implode(';', $css);
}

/**
 * New-tab attributes for a URL that points off-site (mirrors the tag_list
 * shortcode convention). Returns ' target="_blank" rel="noopener noreferrer"'
 * for external links, '' for internal/relative ones.
 *
 * @param string $url
 * @return string
 */
function fw_ext_mega_menu_link_target_attr($url) {
	$host = parse_url($url, PHP_URL_HOST);
	if (!$host) {
		return ''; // relative / same-host anchor
	}
	$home = parse_url(home_url(), PHP_URL_HOST);
	if ($home && strcasecmp($host, $home) === 0) {
		return '';
	}
	return ' target="_blank" rel="noopener noreferrer"';
}

/**
 * Render a MegaMenu column's custom content (Column Content = image / content /
 * widget / raw), for the walker_nav_menu_start_el filter. Returns '' for the
 * default "links" type (or empty payload) so the sub-menu renders normally.
 *
 * @param int|WP_Post $item Column menu item
 * @param string      $type One of 'image' | 'content' | 'widget' | 'raw'
 * @return string
 */
function fw_ext_mega_menu_render_column_content($item, $type) {
	$id = is_object($item) ? $item->ID : intval($item);
	if (!$id) {
		return '';
	}

	switch ($type) {
		case 'image':
			$img = fw_ext_mega_menu_get_item_option($id, 'column', 'content_image', '');
			$url = is_array($img) ? (isset($img['url']) ? $img['url'] : '') : $img;
			if (!$url) {
				return '';
			}
			$alt  = (string) fw_ext_mega_menu_get_item_option($id, 'column', 'content_image_alt', '');
			$link = trim((string) fw_ext_mega_menu_get_item_option($id, 'column', 'content_image_link', ''));

			$html = '<img class="mega-menu-image" src="' . esc_url($url) . '" alt="' . esc_attr($alt) . '" />';
			if ($link !== '') {
				$html = '<a class="mega-menu-image-link" href="' . esc_url($link) . '"'
					. fw_ext_mega_menu_link_target_attr($link) . '>' . $html . '</a>';
			}
			return '<div class="mega-menu-content mega-menu-content--image">' . $html . '</div>';

		case 'content':
			$content = (string) fw_ext_mega_menu_get_item_option($id, 'column', 'content_html', '');
			if (trim($content) === '') {
				return '';
			}
			return '<div class="mega-menu-content mega-menu-content--rich">'
				. do_shortcode(wpautop($content)) . '</div>';

		case 'widget':
			$area = (string) fw_ext_mega_menu_get_item_option($id, 'column', 'content_widget_area', '');
			if ($area === '' || !is_active_sidebar($area)) {
				return '';
			}
			ob_start();
			dynamic_sidebar($area);
			return '<div class="mega-menu-content mega-menu-content--widget">' . ob_get_clean() . '</div>';

		case 'raw':
			$raw = (string) fw_ext_mega_menu_get_item_option($id, 'column', 'content_raw', '');
			if (trim($raw) === '') {
				return '';
			}
			return '<div class="mega-menu-content mega-menu-content--raw">' . do_shortcode($raw) . '</div>';

		case 'cta':
			$cta_heading = trim((string) fw_ext_mega_menu_get_item_option($id, 'column', 'cta_heading', ''));
			$cta_text    = trim((string) fw_ext_mega_menu_get_item_option($id, 'column', 'cta_text', ''));
			$cta_label   = trim((string) fw_ext_mega_menu_get_item_option($id, 'column', 'cta_button_label', ''));
			$cta_link    = trim((string) fw_ext_mega_menu_get_item_option($id, 'column', 'cta_button_link', ''));
			$cta_eyebrow = trim((string) fw_ext_mega_menu_get_item_option($id, 'column', 'cta_eyebrow', ''));
			$cta_img     = fw_ext_mega_menu_get_item_option($id, 'column', 'cta_image', '');
			$cta_img_url = is_array($cta_img) ? (isset($cta_img['url']) ? $cta_img['url'] : '') : $cta_img;
			if ($cta_heading === '' && $cta_text === '' && $cta_label === '' && $cta_img_url === '') {
				return '';
			}
			$cta = '';
			if ($cta_img_url !== '') { $cta .= '<img class="mm-cta-img" src="' . esc_url($cta_img_url) . '" alt="" />'; }
			if ($cta_eyebrow !== '') { $cta .= '<span class="mm-cta-eyebrow">' . esc_html($cta_eyebrow) . '</span>'; }
			if ($cta_heading !== '') { $cta .= '<span class="mm-cta-heading">' . esc_html($cta_heading) . '</span>'; }
			if ($cta_text !== '')    { $cta .= '<span class="mm-cta-text">' . esc_html($cta_text) . '</span>'; }
			if ($cta_label !== '' && $cta_link !== '') {
				$cta .= '<a class="mm-cta-btn" href="' . esc_url($cta_link) . '"'
					. fw_ext_mega_menu_link_target_attr($cta_link) . '>' . esc_html($cta_label) . '</a>';
			}
			return '<div class="mega-menu-content mega-menu-content--cta">' . $cta . '</div>';

		case 'posts':
			$pt    = (string) fw_ext_mega_menu_get_item_option($id, 'column', 'posts_post_type', 'post');
			$count = (int) fw_ext_mega_menu_get_item_option($id, 'column', 'posts_count', 5);
			$order = (string) fw_ext_mega_menu_get_item_option($id, 'column', 'posts_orderby', 'date');
			$thumb = fw_ext_mega_menu_get_item_option($id, 'column', 'posts_thumb', 'yes') === 'yes';
			if ($count < 1) { $count = 5; }
			if (!post_type_exists($pt)) { $pt = 'post'; }
			$order = in_array($order, array('date', 'title', 'rand', 'menu_order'), true) ? $order : 'date';

			$q = new WP_Query(array(
				'post_type'           => $pt,
				'posts_per_page'      => min($count, 20),
				'orderby'             => $order,
				'order'               => ($order === 'title' || $order === 'menu_order') ? 'ASC' : 'DESC',
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
				'post_status'         => 'publish',
			));
			if (!$q->have_posts()) {
				wp_reset_postdata();
				return '';
			}
			$posts_html = '<ul class="mm-posts">';
			while ($q->have_posts()) {
				$q->the_post();
				$posts_html .= '<li class="mm-post"><a href="' . esc_url(get_permalink()) . '">';
				if ($thumb && has_post_thumbnail()) {
					$posts_html .= '<span class="mm-post-thumb">'
						. get_the_post_thumbnail(get_the_ID(), 'thumbnail', array('alt' => '')) . '</span>';
				}
				$posts_html .= '<span class="mm-post-title">' . esc_html(get_the_title()) . '</span></a></li>';
			}
			$posts_html .= '</ul>';
			wp_reset_postdata();
			return '<div class="mega-menu-content mega-menu-content--posts">' . $posts_html . '</div>';

		case 'woo_cart':
			// WooCommerce mini-cart. Renders nothing (gracefully) when Woo is inactive.
			if (!function_exists('WC') || !function_exists('woocommerce_mini_cart') || is_null(WC()->cart)) {
				return '';
			}
			ob_start();
			echo '<div class="widget_shopping_cart_content">';
			woocommerce_mini_cart();
			echo '</div>';
			return '<div class="mega-menu-content mega-menu-content--woocart">' . ob_get_clean() . '</div>';
	}

	return '';
}

/**
 * Check if menu item is a MegaMenu item or is inside a MegaMenu item
 * @param WP_Post $item
 * @return bool
 */
function fw_ext_mega_menu_is_mm_item($item) {
	if (!is_object($item)) {
		if ($item = get_post($item)) {
			$item = wp_setup_nav_menu_item($item);
		} else {
			return false;
		}
	}

	try {
		$mm_items = FW_Cache::get( $cache_key = fw_ext('megamenu')->get_cache_key('/mm_item') );
	} catch (FW_Cache_Not_Found_Exception $e) {
		$mm_items = array();
	}

	if (array_key_exists($item->ID, $mm_items)) {
		return $mm_items[$item->ID];
	}

	$level = 0;
	$cursor_item = array(
		'id' => $item->ID,
		'parent' => intval($item->menu_item_parent),
	);

	do {
		++$level;
		$mm_items[ $cursor_item['id'] ] = 0; // cache all parsed items to prevent posts query on next function call
	} while(
		/**
		 * Only first level parent item can have the "Use as MegaMenu" checkbox.
		 * Other level items also can have set this checkbox when they were on first level,
		 * but it is hidden and must be ignored.
		 */
		$cursor_item['parent'] !== 0
		&&
		($cursor_item = get_post($cursor_item['parent']))
		&&
		($cursor_item = array(
			'id' => $cursor_item->ID,
			'parent' => intval(get_post_meta( $cursor_item->ID, '_menu_item_menu_item_parent', true ))
		))
	);

	$mm_items[$item->ID] = (fw_ext_mega_menu_get_meta($cursor_item['id'], 'enabled') ? $level : 0);

	FW_Cache::set($cache_key, $mm_items);

	return $mm_items[$item->ID];
}

/**
 * Item Options
 * @since 1.1.0
 */
class FW_Db_Options_Model_MegaMenu extends FW_Db_Options_Model {
	protected function get_id()
	{
		return 'megamenu';
	}

	protected function get_fw_storage_params($item_id, array $extra_data = array()) {
		return array( 'megamenu-item' => $item_id );
	}

	/**
	 * @return FW_Extension_Megamenu
	 */
	private function ext() {
		return fw_ext('megamenu');
	}

	protected function _get_cache_key($key, $item_id, array $extra_data = array())
	{
		if ($key === 'options') {
			return '';
		} else {
			return parent::_get_cache_key($key, $item_id, $extra_data);
		}
	}

	protected function get_options($item_id, array $extra_data = array())
	{
		$options = array(
			'type' => array('type' => 'text') // one of the below types
		);

		foreach (array('row', 'column', 'item', 'default') as $type) {
			$options[$type] = array(
				'type' => 'multi',
				'inner-options' => $this->ext()->get_options($type),
			);
		}

		return $options;
	}

	/**
	 * Use theme in meta name
	 * so when the user will change the theme which has other options, there will be no notices/errors/conflicts
	 * @return string
	 */
	public static function get_meta_name() {
		try {
			return FW_Cache::get($cache_key = 'fw:ext:megamenu:items-options:meta-name');
		} catch (FW_Cache_Not_Found_Exception $e) {
			FW_Cache::set(
				$cache_key,
				/**
				 * Use basename() because it can be 'theme-name/theme-name-parent'
				 * then after theme update it becomes 'theme-name-parent'
				 */
				$meta_name = 'fw:ext:mm:io:' . basename(get_template())
			);

			return $meta_name;
		}
	}

	protected function get_values($item_id, array $extra_data = array())
	{
		return FW_WP_Meta::get( 'post', $item_id, self::get_meta_name(), array() );
	}

	protected function set_values($item_id, $values, array $extra_data = array())
	{
		return FW_WP_Meta::set( 'post', $item_id, self::get_meta_name(), $values );
	}

	protected function _init()
	{
		/**
		 * Get item option value from the database
		 *
		 * @param int $item
		 * @param string|null $option_id 'type/option_id' (accepts multikey). null - all options
		 * @param null|mixed $default_value If no option found in the database, this value will be returned
		 *
		 * @return mixed|null
		 */
		function fw_ext_mega_menu_get_db_item_option($item, $option_id = null, $default_value = null) {
			/*if ( ! $item ) {
				global $post;

				if ( ! $post || $post->post_type != 'nav_menu_item' ) {
					return $default_value;
				} else {
					$item = $post;
				}
			} elseif ( ! $item instanceof WP_Post ) {
				if (
					($post = get_post($item))
					&&
					$post->post_type == 'nav_menu_item'
				) {
					$item = $post;
				} else {
					return $default_value;
				}
			}*/

			return FW_Db_Options_Model::_get_instance('megamenu')->get(intval($item), $option_id, $default_value);
		}

		/**
		 * Set item option value in database
		 *
		 * @param int $item
		 * @param string|null $option_id 'type/option_id' (accepts multikey). null - all options
		 * @param $value
		 */
		function fw_ext_mega_menu_set_db_item_option( $item, $option_id, $value ) {
			return FW_Db_Options_Model::_get_instance('megamenu')->set(intval($item), $option_id, $value);
		}
	}
}
new FW_Db_Options_Model_MegaMenu();

/**
 * Whether the current front-end request should load the mega-menu CSS/JS.
 *
 * Scans the menus assigned to the theme's nav-menu LOCATIONS for an enabled
 * top-level mega item. This covers the normal case (header menus placed at a
 * location). Menus rendered elsewhere (a raw wp_nav_menu with an explicit menu,
 * a widget) are caught by the late fallback in hooks.php. Force on/off with the
 * 'fw:ext:megamenu:force-enqueue' filter. Memoized per request.
 *
 * @return bool
 */
function fw_ext_mega_menu_should_enqueue_assets() {
	static $cache = null;
	if ($cache !== null) {
		return $cache;
	}

	/** Filter: return true/false to force the mega-menu assets on/off, or null to auto-detect. */
	$forced = apply_filters('fw:ext:megamenu:force-enqueue', null);
	if ($forced !== null) {
		return $cache = (bool) $forced;
	}

	$cache = false;
	if (!function_exists('get_nav_menu_locations')) {
		return $cache = true; // be safe if called too early
	}

	$menu_ids = array_unique(array_filter((array) get_nav_menu_locations()));
	foreach ($menu_ids as $menu_id) {
		$items = wp_get_nav_menu_items($menu_id);
		if (empty($items)) {
			continue;
		}
		foreach ($items as $item) {
			if ((int) $item->menu_item_parent === 0 && fw_ext_mega_menu_get_meta($item, 'enabled')) {
				return $cache = true;
			}
		}
	}

	return $cache;
}

/**
 * Enqueue the mega-menu front-end assets (icon font + baseline CSS/JS + behavior
 * config). Idempotent — safe to call from both the wp_enqueue_scripts pass
 * (static.php) and the late wp_nav_menu fallback (hooks.php).
 */
function fw_ext_mega_menu_do_enqueue() {
	static $done = false;
	if ($done) {
		return;
	}
	$done = true;

	$ext = fw_ext('megamenu');
	if (!$ext) {
		return;
	}

	/** Filters whether the mega menu enqueues its icon-font CSS on the front end. */
	if (apply_filters('fw:ext:megamenu:enqueue-icon-css', true)) {
		$icon_option = $ext->get_icon_option();

		if (
			$icon_option['type'] === 'icon'
			&&
			($icon_v2 = fw()->backend->option_type('icon'))
			&&
			isset($icon_v2->packs_loader)
			&&
			$icon_v2->packs_loader
		) {
			$icon_v2->packs_loader->enqueue_frontend_css();
		} else {
			wp_enqueue_style(
				'font-awesome',
				fw_get_framework_directory_uri('/static/libs/font-awesome/css/font-awesome.min.css'),
				array(),
				fw()->manifest->get_version()
			);
		}
	}

	/** Filters whether the mega menu's baseline front-end CSS/JS and behavior config are enqueued (opt-out point). */
	if (apply_filters('fw:ext:megamenu:enqueue-frontend-css', true)) {
		wp_enqueue_style(
			'fw-ext-megamenu',
			$ext->get_uri('/static/css/frontend.css'),
			array(),
			$ext->manifest->get_version()
		);
		wp_enqueue_script(
			'fw-ext-megamenu',
			$ext->get_uri('/static/js/frontend.js'),
			array(),
			$ext->manifest->get_version(),
			true
		);

		wp_localize_script(
			'fw-ext-megamenu',
			'_fw_mega_menu',
			/** Filters the mega menu front-end behavior config localized to script (hover/click, drawer id, i18n). */
			apply_filters('fw:ext:megamenu:frontend-config', array(
				'openOn'   => 'hover', // 'hover' | 'click'
				/** Element id of the host theme's off-canvas nav drawer; when present the theme owns mobile behavior. */
				'drawerId' => apply_filters('fw:ext:megamenu:drawer-id', 'primary-navigation-drawer'),
				// Hover intent: open only after a brief deliberate hover, and keep the panel open
				// through a short exit grace — filters accidental pass-throughs and stops flicker.
				// Set 'hoverIntent' => false to restore the instant-hover behavior.
				'hoverIntent' => true,
				'openDelay'   => 100, // ms before a hovered panel opens
				'closeDelay'  => 250, // ms the panel stays open after the pointer leaves
				'i18n'     => array(
					'toggleSubmenu' => __('Toggle submenu', 'fw'),
				),
			))
		);
	}
}

/**
 * Export a top-level mega menu item as a portable layout (row options + the full tree
 * of child columns/items with their options). Import re-creates it on another item.
 *
 * @param int|WP_Post $top_id A top-level (enabled) mega menu item
 * @return array|null
 */
function fw_ext_mega_menu_export_layout($top_id) {
	$top = is_object($top_id) ? $top_id : get_post($top_id);
	if (!$top) {
		return null;
	}
	$menus = wp_get_object_terms($top->ID, 'nav_menu');
	$menu_id = (!is_wp_error($menus) && !empty($menus)) ? (int) $menus[0]->term_id : 0;
	if (!$menu_id) {
		return null;
	}
	$top = wp_setup_nav_menu_item($top);

	return array(
		'_fw_mm_layout' => 1,
		'version'       => 1,
		'exported'      => gmdate('c'),
		'top'           => array(
			'title'       => $top->title,
			'row_options' => fw_ext_mega_menu_get_item_option($top->ID, 'row', null, array()),
		),
		'children'      => fw_ext_mega_menu_layout_children($top->ID, $menu_id),
	);
}

/**
 * Recursively collect the child menu items (title/url + mega options) of a mega item.
 * @internal
 */
function fw_ext_mega_menu_layout_children($parent_id, $menu_id) {
	$out = array();
	$all = wp_get_nav_menu_items($menu_id);
	if (empty($all)) {
		return $out;
	}
	foreach ($all as $it) {
		if ((int) $it->menu_item_parent !== (int) $parent_id) {
			continue;
		}
		$type = fw_ext_mega_menu_item_type($it); // 'column' | 'item' | 'default'
		$out[] = array(
			'title'       => $it->title,
			'url'         => $it->url,
			// Preserve the underlying object link (a real Page/Post/term) so import can re-create the
			// same kind of item on the same site, instead of flattening everything to a custom URL.
			'object_type' => $it->type,        // 'custom' | 'post_type' | 'taxonomy' | 'post_type_archive'
			'object'      => $it->object,      // 'page' | 'post' | taxonomy name …
			'object_id'   => (int) $it->object_id,
			'target'      => $it->target,
			'attr_title'  => $it->attr_title,
			'xfn'         => $it->xfn,
			'description' => $it->description,
			'classes'     => is_array($it->classes) ? array_values(array_filter($it->classes)) : array(),
			'mm_type'     => $type,
			'mm_options'  => fw_ext_mega_menu_get_item_option($it->ID, $type, null, array()),
			'mm_flags'    => array(
				'title-off' => (bool) fw_ext_mega_menu_get_meta($it, 'title-off'),
				'new-row'   => (bool) fw_ext_mega_menu_get_meta($it, 'new-row'),
			),
			'children'    => fw_ext_mega_menu_layout_children($it->ID, $menu_id),
		);
	}
	return $out;
}

/**
 * Import a layout (from fw_ext_mega_menu_export_layout) onto a target menu item: enables
 * mega on it, applies the row options, and re-creates the child columns/items beneath it.
 *
 * @param array $data      Decoded layout
 * @param int   $target_id Target top-level menu item id
 * @return int|WP_Error    Number of child items created, or WP_Error
 */
function fw_ext_mega_menu_import_layout($data, $target_id) {
	if (!is_array($data) || empty($data['_fw_mm_layout'])) {
		return new WP_Error('mm_bad_data', __('That does not look like an exported mega menu layout.', 'fw'));
	}
	$target = get_post($target_id);
	if (!$target) {
		return new WP_Error('mm_no_target', __('Target menu item not found.', 'fw'));
	}
	$menus = wp_get_object_terms($target_id, 'nav_menu');
	$menu_id = (!is_wp_error($menus) && !empty($menus)) ? (int) $menus[0]->term_id : 0;
	if (!$menu_id) {
		return new WP_Error('mm_no_menu', __('The target menu item is not part of a menu.', 'fw'));
	}
	require_once ABSPATH . 'wp-admin/includes/nav-menu.php';

	// Enable mega on the target + apply the row options.
	fw_ext_mega_menu_update_meta($target_id, array('enabled' => true));
	if (!empty($data['top']['row_options']) && is_array($data['top']['row_options'])) {
		fw_ext_mega_menu_set_db_item_option($target_id, 'row', $data['top']['row_options']);
	}

	$count = 0;
	fw_ext_mega_menu_import_children(isset($data['children']) ? $data['children'] : array(), $menu_id, (int) $target_id, $count);

	try { FW_Cache::del(fw_ext('megamenu')->get_cache_key()); } catch (Exception $e) {}
	return $count;
}

/**
 * Recursively re-create child menu items (as custom links) + apply their mega options.
 * @internal
 */
function fw_ext_mega_menu_import_children($children, $menu_id, $parent_id, &$count) {
	if (!is_array($children)) {
		return;
	}
	foreach ($children as $node) {
		if (!is_array($node)) {
			continue;
		}
		$menu_args = array(
			'menu-item-title'       => isset($node['title']) ? $node['title'] : '',
			'menu-item-description' => isset($node['description']) ? $node['description'] : '',
			'menu-item-attr-title'  => isset($node['attr_title']) ? $node['attr_title'] : '',
			'menu-item-target'      => isset($node['target']) ? $node['target'] : '',
			'menu-item-xfn'         => isset($node['xfn']) ? $node['xfn'] : '',
			'menu-item-classes'     => (isset($node['classes']) && is_array($node['classes'])) ? implode(' ', $node['classes']) : '',
			'menu-item-status'      => 'publish',
			'menu-item-parent-id'   => (int) $parent_id,
		);

		// Re-create a real object link (Page/Post/term) when it still exists on this site;
		// otherwise fall back to a custom URL (e.g. importing onto a different site).
		$obj_type = isset($node['object_type']) ? (string) $node['object_type'] : 'custom';
		$obj      = isset($node['object']) ? (string) $node['object'] : '';
		$obj_id   = isset($node['object_id']) ? (int) $node['object_id'] : 0;
		$linked   = false;
		if ($obj_type === 'post_type' && $obj && $obj_id && get_post($obj_id) && get_post_type($obj_id) === $obj) {
			$menu_args['menu-item-type']      = 'post_type';
			$menu_args['menu-item-object']    = $obj;
			$menu_args['menu-item-object-id'] = $obj_id;
			$linked = true;
		} elseif ($obj_type === 'taxonomy' && $obj && $obj_id && !is_wp_error(get_term($obj_id))) {
			$menu_args['menu-item-type']      = 'taxonomy';
			$menu_args['menu-item-object']    = $obj;
			$menu_args['menu-item-object-id'] = $obj_id;
			$linked = true;
		}
		if (!$linked) {
			$menu_args['menu-item-type'] = 'custom';
			$menu_args['menu-item-url']  = isset($node['url']) ? $node['url'] : '';
		}

		$new_id = wp_update_nav_menu_item($menu_id, 0, $menu_args);
		if (is_wp_error($new_id) || !$new_id) {
			continue;
		}
		$count++;

		$flags = array();
		if (!empty($node['mm_flags']['title-off'])) { $flags['title-off'] = true; }
		if (!empty($node['mm_flags']['new-row']))   { $flags['new-row'] = true; }
		if ($flags) {
			fw_ext_mega_menu_update_meta($new_id, $flags);
		}
		if (!empty($node['mm_type']) && !empty($node['mm_options']) && is_array($node['mm_options'])) {
			fw_ext_mega_menu_set_db_item_option($new_id, (string) $node['mm_type'], $node['mm_options']);
		}
		if (!empty($node['children'])) {
			fw_ext_mega_menu_import_children($node['children'], $menu_id, $new_id, $count);
		}
	}
}

/**
 * Render a preview of a single mega item's assembled panel (its columns/items), for the admin
 * "Preview" overlay. Renders the item's menu with the mega walker, extracts just that item's <li>
 * (which contains the panel), and forces it open. Uses the current SAVED options.
 *
 * @param int $top_id Enabled top-level mega item id
 * @return string HTML (the <li> with its .mega-menu panel), or '' on failure
 */
function fw_ext_mega_menu_render_preview($top_id) {
	$top_id = (int) $top_id;
	$top = get_post($top_id);
	if (!$top) {
		return '';
	}
	$menus = wp_get_object_terms($top_id, 'nav_menu');
	$menu_id = (!is_wp_error($menus) && !empty($menus)) ? (int) $menus[0]->term_id : 0;
	if (!$menu_id) {
		return '';
	}

	$full = wp_nav_menu(array(
		'menu'        => $menu_id,
		'echo'        => false,
		'container'   => false,
		'items_wrap'  => '<ul class="primary-menu">%3$s</ul>',
		'fallback_cb' => false,
	));
	if (!$full) {
		return '';
	}

	if (!class_exists('DOMDocument')) {
		return $full; // no DOM ext — return the whole menu (still previewable)
	}
	$dom = new DOMDocument();
	$prev = libxml_use_internal_errors(true);
	$dom->loadHTML('<?xml encoding="utf-8"?><div id="fw-mm-preview-root">' . $full . '</div>');
	libxml_clear_errors();
	libxml_use_internal_errors($prev);

	$xpath = new DOMXPath($dom);
	$nodes = $xpath->query('//li[@id="menu-item-' . $top_id . '"]');
	if (!$nodes->length) {
		return '';
	}
	$li = $nodes->item(0);
	// Force the panel open for the still preview.
	$li->setAttribute('class', trim($li->getAttribute('class') . ' is-open'));
	return $dom->saveHTML($li);
}
