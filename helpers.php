<?php if (!defined('FW')) die('Forbidden');

/**
 * @param int|object $post
 * @param $key
 * @param null $default
 * @return mixed
 */
function fw_ext_mega_menu_get_meta($post, $key, $default = null) {
	return _fw_ext_mega_menu_meta($post, $key, $default);
}

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
		$w = 90; $h = 44; $accent = '#2271b1'; $grey = '#c3c8cf';
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
