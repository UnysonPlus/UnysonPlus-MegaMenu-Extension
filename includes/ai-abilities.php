<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * AI Assistant abilities for navigation menus and the Mega Menu.
 *
 * WordPress core has no menu abilities, and a menu is what turns pages into a site, so this extension
 * (which owns the menus' mega-menu layer) provides the plain-menu abilities too:
 *
 *   menus-list          menus, their locations and item trees (with mega-menu settings), theme locations
 *   menus-create        a NEW menu from a nested item list, optionally placed in a theme location
 *   menus-add-items     add items (nested) to an existing menu, optionally under a parent item
 *   menus-assign        put a menu in a theme location (header, footer …)
 *   menus-remove-item   remove an item (and its children)
 *   megamenu-set-item   turn a top-level item into a mega menu and set row / column / item options
 *
 * Programmatic menu writes do not pass through this extension's admin save handler (it only runs on
 * the Menus screen's form POST), so mega-menu data is written with fw_ext_mega_menu_update_meta() and
 * fw_ext_mega_menu_set_db_item_option() directly. Every write is snapshotted for undo-change: new
 * menus are deleted, new items trashed, removed items restored, location / meta changes put back.
 */

if ( ! function_exists( 'fw_ext_megamenu_ai_register' ) ) :

	/** Item types by depth, as the Mega Menu understands them. */
	function fw_ext_megamenu_ai_type( $depth, $top_enabled ) {
		if ( ! $top_enabled ) {
			return 'default';
		}
		return $depth === 0 ? 'row' : ( $depth === 1 ? 'column' : 'item' );
	}

	/**
	 * @param int $menu_id
	 * @return array Nested item tree.
	 */
	function fw_ext_megamenu_ai_tree( $menu_id ) {
		$items = wp_get_nav_menu_items( $menu_id, array( 'post_status' => 'publish' ) );
		if ( ! $items ) {
			return array();
		}
		$by_parent = array();
		foreach ( $items as $it ) {
			$by_parent[ (int) $it->menu_item_parent ][] = $it;
		}
		$build = function ( $parent, $depth, $top_enabled ) use ( &$build, $by_parent ) {
			$out = array();
			foreach ( $by_parent[ $parent ] ?? array() as $it ) {
				$mega    = (array) get_post_meta( $it->ID, 'mega-menu', true );
				$enabled = $depth === 0 ? ! empty( $mega['enabled'] ) : $top_enabled;
				$row     = array(
					'item_id' => (int) $it->ID,
					'title'   => $it->title,
					'url'     => $it->url,
					'kind'    => $it->type === 'post_type' ? $it->object . ' #' . $it->object_id : $it->type,
				);
				if ( $depth === 0 && $enabled ) {
					$row['mega_menu'] = true;
				}
				if ( ! empty( $mega['title-off'] ) ) {
					$row['title_off'] = true;
				}
				if ( ! empty( $mega['new-row'] ) ) {
					$row['new_row'] = true;
				}
				if ( $enabled && function_exists( 'fw_ext_mega_menu_get_db_item_option' ) ) {
					$type = fw_ext_megamenu_ai_type( $depth, true );
					$opts = fw_ext_mega_menu_get_db_item_option( $it->ID, $type );
					if ( is_array( $opts ) && $opts ) {
						$row['mega_options'] = array_filter( $opts, static function ( $v ) {
							return $v !== '' && $v !== array() && $v !== null;
						} );
					}
				}
				$kids = $build( (int) $it->ID, $depth + 1, $enabled );
				if ( $kids ) {
					$row['children'] = $kids;
				}
				$out[] = $row;
			}
			return $out;
		};
		return $build( 0, 0, false );
	}

	/**
	 * Add a nested list of items to a menu.
	 *
	 * @param int   $menu_id
	 * @param array $items   [{ title?, page_id? | post_id? | url?, children? }]
	 * @param int   $parent  Parent item id (0 = top level).
	 * @param array $created Collected new item ids (by reference).
	 * @return true|WP_Error
	 */
	function fw_ext_megamenu_ai_add( $menu_id, array $items, $parent, array &$created ) {
		foreach ( array_values( $items ) as $i => $item ) {
			if ( ! is_array( $item ) ) {
				return new WP_Error( 'fw_mm_ai_item', "Item $i must be an object." );
			}
			$args = array(
				'menu-item-status'    => 'publish',
				'menu-item-parent-id' => (int) $parent,
				'menu-item-position'  => 0,
			);
			$object_id = (int) ( $item['page_id'] ?? $item['post_id'] ?? 0 );
			if ( $object_id ) {
				$post = get_post( $object_id );
				if ( ! $post || $post->post_status === 'trash' ) {
					return new WP_Error( 'fw_mm_ai_post', sprintf( 'No post with id %d.', $object_id ) );
				}
				$args += array(
					'menu-item-type'      => 'post_type',
					'menu-item-object'    => $post->post_type,
					'menu-item-object-id' => $object_id,
					'menu-item-title'     => isset( $item['title'] ) ? sanitize_text_field( (string) $item['title'] ) : '',
				);
			} else {
				$url = isset( $item['url'] ) ? esc_url_raw( (string) $item['url'] ) : '';
				if ( $url === '' && empty( $item['children'] ) ) {
					return new WP_Error( 'fw_mm_ai_target', sprintf( 'Item "%s" needs a page_id, post_id or url (a parent with children may use url "#").', $item['title'] ?? $i ) );
				}
				$args += array(
					'menu-item-type'  => 'custom',
					'menu-item-url'   => $url !== '' ? $url : '#',
					'menu-item-title' => sanitize_text_field( (string) ( $item['title'] ?? __( 'Menu item', 'fw' ) ) ),
				);
			}
			$id = wp_update_nav_menu_item( $menu_id, 0, $args );
			if ( is_wp_error( $id ) ) {
				return $id;
			}
			$created[] = (int) $id;
			if ( ! empty( $item['children'] ) && is_array( $item['children'] ) ) {
				$r = fw_ext_megamenu_ai_add( $menu_id, $item['children'], (int) $id, $created );
				if ( is_wp_error( $r ) ) {
					return $r;
				}
			}
		}
		return true;
	}

	/** @return string The theme_mods option that holds nav_menu_locations. */
	function fw_ext_megamenu_ai_mods_option() {
		return 'theme_mods_' . get_option( 'stylesheet' );
	}

	/**
	 * @param int    $menu_id
	 * @param string $location
	 * @return true|WP_Error
	 */
	function fw_ext_megamenu_ai_assign( $menu_id, $location ) {
		$registered = get_registered_nav_menus();
		if ( ! isset( $registered[ $location ] ) ) {
			return new WP_Error( 'fw_mm_ai_location', sprintf( 'Unknown menu location "%s" (registered: %s).', $location, implode( ', ', array_keys( $registered ) ) ) );
		}
		$locations              = (array) get_theme_mod( 'nav_menu_locations', array() );
		$locations[ $location ] = (int) $menu_id;
		set_theme_mod( 'nav_menu_locations', $locations );
		return true;
	}

	/** @var array The item schema shared by the create / add abilities. */
	function fw_ext_megamenu_ai_items_schema() {
		return array(
			'type'        => 'array',
			'items'       => array( 'type' => 'object' ),
			'description' => 'Menu items: [{ title?, page_id? | post_id? | url?, children?: [ …same shape… ] }]. Link a page with page_id (the title defaults to the page title); an external or anchor link with url; a parent that only opens a dropdown may use url "#".',
		);
	}

	function fw_ext_megamenu_ai_register() {
		if ( ! function_exists( 'fw_ai_register_ability' ) ) {
			return;
		}

		fw_ai_register_ability( 'menus-list', array(
			'label'       => __( 'List menus', 'fw' ),
			'description' => 'Every navigation menu with the theme locations it is shown in and its item tree (item_id, title, url, what it links to, children), including mega-menu settings on items; plus the theme\'s registered menu locations (primary header, footer …) and which menu each shows.',
			'permission'  => 'edit_theme_options',
			'readonly'    => true,
			'execute'     => function () {
				$locations = (array) get_nav_menu_locations();
				$menus     = array();
				foreach ( wp_get_nav_menus() as $m ) {
					$menus[] = array(
						'menu_id'   => (int) $m->term_id,
						'name'      => $m->name,
						'locations' => array_keys( array_filter( $locations, static function ( $id ) use ( $m ) {
							return (int) $id === (int) $m->term_id;
						} ) ),
						'items'     => fw_ext_megamenu_ai_tree( $m->term_id ),
					);
				}
				$registered = array();
				foreach ( get_registered_nav_menus() as $loc => $label ) {
					$registered[] = array( 'location' => $loc, 'label' => $label, 'menu_id' => (int) ( $locations[ $loc ] ?? 0 ) );
				}
				return array( 'menus' => $menus, 'locations' => $registered );
			},
		) );

		fw_ai_register_ability( 'menus-create', array(
			'label'       => __( 'Create a menu', 'fw' ),
			'description' => 'Creates a NEW navigation menu from a nested item list and, optionally, shows it in a theme location (see menus_list for locations — putting it in a location replaces the menu shown there, live). Undo with undo_change (deletes the new menu and restores the location).',
			'input'       => array(
				'name'     => array( 'type' => 'string', 'minLength' => 1 ),
				'items'    => fw_ext_megamenu_ai_items_schema(),
				'location' => array( 'type' => 'string' ),
			),
			'required'    => array( 'name', 'items' ),
			'permission'  => 'edit_theme_options',
			'execute'     => function ( $in ) {
				$name = sanitize_text_field( (string) $in['name'] );
				if ( wp_get_nav_menu_object( $name ) ) {
					return new WP_Error( 'fw_mm_ai_exists', sprintf( 'A menu named "%s" already exists — use menus_add_items, or another name.', $name ) );
				}
				$menu_id = wp_create_nav_menu( $name );
				if ( is_wp_error( $menu_id ) ) {
					return $menu_id;
				}
				$spec = array( 'created_menus' => array( $menu_id ) );
				if ( ! empty( $in['location'] ) ) {
					$spec['options'] = array( fw_ext_megamenu_ai_mods_option() );
				}
				$rev     = fw_ai_snapshot( $spec, 'unysonplus/menus-create', sprintf( 'Created menu "%s"', $name ) );
				$created = array();
				$r       = fw_ext_megamenu_ai_add( $menu_id, (array) $in['items'], 0, $created );
				if ( is_wp_error( $r ) ) {
					wp_delete_nav_menu( $menu_id );
					return $r;
				}
				if ( ! empty( $in['location'] ) ) {
					$a = fw_ext_megamenu_ai_assign( $menu_id, (string) $in['location'] );
					if ( is_wp_error( $a ) ) {
						return $a;
					}
				}
				return array(
					'ok'               => true,
					'message'          => sprintf( 'Created menu "%s" with %d item(s)%s.', $name, count( $created ), ! empty( $in['location'] ) ? ' in location ' . $in['location'] : '' ),
					'menu_id'          => (int) $menu_id,
					'items'            => fw_ext_megamenu_ai_tree( $menu_id ),
					'undo_revision_id' => $rev,
				);
			},
		) );

		fw_ai_register_ability( 'menus-add-items', array(
			'label'       => __( 'Add menu items', 'fw' ),
			'description' => 'Adds items (nested allowed) to an existing menu, at the top level or under parent_item_id. Undo with undo_change (the new items are removed).',
			'input'       => array(
				'menu_id'        => array( 'type' => 'integer' ),
				'items'          => fw_ext_megamenu_ai_items_schema(),
				'parent_item_id' => array( 'type' => 'integer' ),
			),
			'required'    => array( 'menu_id', 'items' ),
			'permission'  => 'edit_theme_options',
			'execute'     => function ( $in ) {
				$menu_id = (int) $in['menu_id'];
				if ( ! is_nav_menu( $menu_id ) ) {
					return new WP_Error( 'fw_mm_ai_menu', 'No menu with that menu_id.' );
				}
				$parent = (int) ( $in['parent_item_id'] ?? 0 );
				if ( $parent && ( get_post_type( $parent ) !== 'nav_menu_item' ) ) {
					return new WP_Error( 'fw_mm_ai_parent', 'parent_item_id is not a menu item.' );
				}
				$created = array();
				$r       = fw_ext_megamenu_ai_add( $menu_id, (array) $in['items'], $parent, $created );
				$rev     = fw_ai_snapshot( array( 'created_posts' => $created ), 'unysonplus/menus-add-items', sprintf( 'Added %d item(s) to menu %d', count( $created ), $menu_id ) );
				if ( is_wp_error( $r ) ) {
					return $r;
				}
				return array(
					'ok'               => true,
					'message'          => sprintf( 'Added %d item(s).', count( $created ) ),
					'menu_id'          => $menu_id,
					'items'            => fw_ext_megamenu_ai_tree( $menu_id ),
					'undo_revision_id' => $rev,
				);
			},
		) );

		fw_ai_register_ability( 'menus-assign', array(
			'label'       => __( 'Show a menu in a location', 'fw' ),
			'description' => 'Puts a menu in a theme location (e.g. primary = the main header menu), replacing whatever was shown there. Live immediately; undo with undo_change.',
			'input'       => array(
				'menu_id'  => array( 'type' => 'integer' ),
				'location' => array( 'type' => 'string' ),
			),
			'required'    => array( 'menu_id', 'location' ),
			'permission'  => 'edit_theme_options',
			'idempotent'  => true,
			'execute'     => function ( $in ) {
				if ( ! is_nav_menu( (int) $in['menu_id'] ) ) {
					return new WP_Error( 'fw_mm_ai_menu', 'No menu with that menu_id.' );
				}
				$rev = fw_ai_snapshot( array( 'options' => array( fw_ext_megamenu_ai_mods_option() ) ), 'unysonplus/menus-assign', sprintf( 'Put menu %d in location %s', (int) $in['menu_id'], $in['location'] ) );
				$r   = fw_ext_megamenu_ai_assign( (int) $in['menu_id'], (string) $in['location'] );
				if ( is_wp_error( $r ) ) {
					return $r;
				}
				return array( 'ok' => true, 'message' => sprintf( 'Menu now shown in "%s".', $in['location'] ), 'undo_revision_id' => $rev );
			},
		) );

		fw_ai_register_ability( 'menus-remove-item', array(
			'label'       => __( 'Remove a menu item', 'fw' ),
			'description' => 'Removes one menu item and its children from its menu. Confirm with the person before removing items they created. Undo with undo_change.',
			'input'       => array( 'item_id' => array( 'type' => 'integer' ) ),
			'required'    => array( 'item_id' ),
			'permission'  => 'edit_theme_options',
			'destructive' => true,
			'execute'     => function ( $in ) {
				$id = (int) $in['item_id'];
				if ( get_post_type( $id ) !== 'nav_menu_item' ) {
					return new WP_Error( 'fw_mm_ai_item', 'item_id is not a menu item.' );
				}
				$ids   = array( $id );
				$queue = array( $id );
				while ( $queue ) {
					$pid  = array_shift( $queue );
					$kids = get_posts( array( 'post_type' => 'nav_menu_item', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => '_menu_item_menu_item_parent', 'meta_value' => (string) $pid ) );
					$ids   = array_merge( $ids, $kids );
					$queue = array_merge( $queue, $kids );
				}
				$rev = fw_ai_snapshot( array( 'trashed_posts' => $ids ), 'unysonplus/menus-remove-item', sprintf( 'Removed menu item "%s"', get_the_title( $id ) ) );
				foreach ( $ids as $pid ) {
					wp_trash_post( $pid );
				}
				return array( 'ok' => true, 'message' => sprintf( 'Removed %d item(s).', count( $ids ) ), 'undo_revision_id' => $rev );
			},
		) );

		fw_ai_register_ability( 'megamenu-set-item', array(
			'label'       => __( 'Set mega-menu options on a menu item', 'fw' ),
			'description' => 'Mega menu: on a TOP-LEVEL item, enabled: true turns its dropdown into a mega menu; its children become COLUMNS and their children the column\'s items. title_off hides an item\'s label (columns / items), new_row starts a new row of columns. options sets the item\'s mega-menu options for its level — row (top item: dropdown_width, mm_layout columns|tabs, bg_image …), column (width auto|1/2|1/3|2/3|1/4|3/4|1/5|1/6, content_type links|image|content|cta|posts|widget, cta_heading, cta_text, cta_button_label, cta_button_link, content_html …) or item (item_subtitle, badge_text, badge_color, item_image …); every level also takes icon and hide_on. Undo with undo_change.',
			'input'       => array(
				'item_id'   => array( 'type' => 'integer' ),
				'enabled'   => array( 'type' => 'boolean' ),
				'title_off' => array( 'type' => 'boolean' ),
				'new_row'   => array( 'type' => 'boolean' ),
				'options'   => array( 'type' => 'object' ),
			),
			'required'    => array( 'item_id' ),
			'permission'  => 'edit_theme_options',
			'idempotent'  => true,
			'execute'     => 'fw_ext_megamenu_ai_set_item',
		) );
	}
	add_action( 'fw_ai_assistant_register_abilities', 'fw_ext_megamenu_ai_register' );

	/**
	 * @param array $in
	 * @return array|WP_Error
	 */
	function fw_ext_megamenu_ai_set_item( $in ) {
		$id = (int) $in['item_id'];
		if ( get_post_type( $id ) !== 'nav_menu_item' ) {
			return new WP_Error( 'fw_mm_ai_item', 'item_id is not a menu item.' );
		}
		// Depth + whether the top-level ancestor is a mega menu.
		$depth = 0;
		$top   = $id;
		$p     = (int) get_post_meta( $id, '_menu_item_menu_item_parent', true );
		while ( $p ) {
			$depth++;
			$top = $p;
			$p   = (int) get_post_meta( $p, '_menu_item_menu_item_parent', true );
		}
		if ( isset( $in['enabled'] ) && $depth !== 0 ) {
			return new WP_Error( 'fw_mm_ai_depth', 'Only a top-level item can be turned into a mega menu (enabled).' );
		}
		$top_mega    = (array) get_post_meta( $top, 'mega-menu', true );
		$top_enabled = isset( $in['enabled'] ) && $depth === 0 ? (bool) $in['enabled'] : ! empty( $top_mega['enabled'] );
		$type        = fw_ext_megamenu_ai_type( $depth, $top_enabled );

		$errors = array();
		$opts   = isset( $in['options'] ) ? (array) json_decode( wp_json_encode( $in['options'] ), true ) : array();
		if ( $opts ) {
			if ( ! $top_enabled ) {
				return new WP_Error( 'fw_mm_ai_off', 'Mega-menu options only apply inside a mega menu: enable it on the top-level item first.' );
			}
			$schema = fw_extract_only_options( (array) fw_ext( 'megamenu' )->get_options( $type ) );
			foreach ( $opts as $k => $v ) {
				if ( ! isset( $schema[ $k ] ) ) {
					$errors[] = sprintf( '%s: not a %s option (valid: %s).', $k, $type, implode( ', ', array_keys( $schema ) ) );
					continue;
				}
				FW_AI_Schema::check_deep( $schema[ $k ], $v, $k, $errors );
			}
		}
		if ( $errors ) {
			return new WP_Error( 'fw_mm_ai_invalid', 'Nothing was changed: ' . implode( ' | ', $errors ) );
		}

		$meta_name = class_exists( 'FW_Db_Options_Model_MegaMenu' ) ? FW_Db_Options_Model_MegaMenu::get_meta_name() : '';
		$rev       = fw_ai_snapshot( array( 'post_meta' => array( $id => array_filter( array( 'mega-menu', $meta_name ) ) ) ), 'unysonplus/megamenu-set-item', sprintf( 'Mega-menu settings on "%s"', get_the_title( $id ) ) );

		$flags = array();
		if ( isset( $in['enabled'] ) ) {
			$flags['enabled'] = (bool) $in['enabled'];
		}
		if ( isset( $in['title_off'] ) ) {
			$flags['title-off'] = (bool) $in['title_off'];
		}
		if ( isset( $in['new_row'] ) ) {
			$flags['new-row'] = (bool) $in['new_row'];
		}
		if ( $flags ) {
			$merged = array_filter( array_merge( (array) get_post_meta( $id, 'mega-menu', true ), $flags ) );
			update_post_meta( $id, 'mega-menu', $merged );
		}
		if ( $opts ) {
			$current = fw_ext_mega_menu_get_db_item_option( $id, $type );
			fw_ext_mega_menu_set_db_item_option( $id, null, array(
				'type' => $type,
				$type  => array_merge( is_array( $current ) ? $current : array(), $opts ),
			) + (array) fw_ext_mega_menu_get_db_item_option( $id ) );
		}
		return array(
			'ok'               => true,
			'message'          => sprintf( 'Updated "%s" (%s).', get_the_title( $id ), $type ),
			'type'             => $type,
			'mega'             => (array) get_post_meta( $id, 'mega-menu', true ),
			'options'          => fw_ext_mega_menu_get_db_item_option( $id, $type ),
			'undo_revision_id' => $rev,
		);
	}

endif;
