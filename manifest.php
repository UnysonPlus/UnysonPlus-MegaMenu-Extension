<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * Changelog ----------------------------------------------------------------
 *
 * 1.1.21 - Icon moved into the per-item "Settings" modal + icon positions.
 *          The standalone "Add/Edit Icon" control (its own bare one-option
 *          modal) is retired; the icon is now an icon-v2 option inside the
 *          Settings modal alongside Item Image / Subtitle / Badge, so it now
 *          supports font icons, emoji, SVG and uploaded images - rendered via
 *          the shortcodes helper sc_icon_render(). A new "Icon Position" option
 *          places the icon Left (default), Right, or Stacked-left (a larger
 *          boxed icon centred against the title + subtitle). Icons already
 *          saved by the old control are still read as a fallback
 *          (fw_ext_mega_menu_item_icon), so nothing is lost and no migration
 *          runs. The Settings modal also gained a visual image-picker for the
 *          column Width, and the theme's Header - Mega Menu tab added Base Font
 *          Size and a Full-width Panel Style (edge-to-edge vs boxed card).
 *
 * 1.1.15 - Premium upgrade: token-driven styling, animation/behavior bridge,
 *          and column content types. The baseline stylesheet now consumes a
 *          --mm-* custom-property contract (panel design, headings, items,
 *          icons, animation, responsive) so a host theme can restyle every
 *          mega panel site-wide from Theme Settings while the extension keeps
 *          working standalone via the fallbacks. Front-end behavior is fed
 *          through a new 'fw:ext:megamenu:frontend-config' filter (open on
 *          hover vs click); the JS now detects a host off-canvas drawer and
 *          steps aside so it never double-binds the mobile toggle. Columns
 *          gained a "Column Content" type — Image (optionally linked, external
 *          links open in a new tab), Rich content (HTML + shortcodes), a
 *          Widget area, or Raw HTML — rendered in place of the sub-menu, so a
 *          column can hold real content, not just links. The column Settings
 *          modal is now medium-sized to fit the content editors.
 *
 * 1.1.5 - Activated per-item "Settings" + baseline styling + modern icons.
 *         The previously-empty row/column/item/default option sets are now
 *         populated, so the long-dormant per-item "Settings" modal finally
 *         appears and does something: dropdown width (default / full-width /
 *         custom) plus background colour/image for the row panel, width
 *         fraction + alignment + background for columns, a text/colour badge
 *         for items, and an "Extra CSS Class" everywhere. The front-end
 *         walker now reads those values and emits matching classes
 *         (mm-col-1-3, mm-col-align-center, mega-menu-full, …) and inline
 *         styles. Ships an opt-out baseline stylesheet (static/css/frontend.css)
 *         so columns, the dropdown panel and a mobile accordion work without
 *         theme CSS - toggle it with the
 *         'fw:ext:megamenu:enqueue-frontend-css' filter. The link icon picker
 *         now defaults to the framework's modern multi-pack "icon-v2" type
 *         (Font Awesome 6); revert with the 'fw:ext:megamenu:icon-option'
 *         filter. Hardening: the item-values AJAX endpoint now verifies a
 *         nonce.
 */

$manifest = array();

$manifest['name']        = __( 'Mega Menu', 'fw' );
$manifest['slug']        = 'unysonplus-megamenu';
$manifest['description'] = __( 
	'The Mega Menu extension adds a user-friendly drop down menu that will let you easily create highly customized menu configurations.', 
	'fw' 
);

$manifest['version']     = '1.1.43';
$manifest['display']     = true;
$manifest['standalone']  = true;

// Repository Info
$manifest['github_update'] = 'UnysonPlus/UnysonPlus-MegaMenu-Extension';
$manifest['github_repo']   = 'https://github.com/UnysonPlus/UnysonPlus-MegaMenu-Extension';
$manifest['github_branch'] = 'master';

// Author Info
$manifest['author']     = 'UnysonPlus';
$manifest['author_uri'] = 'https://www.lastimosa.com.ph/unysonplus';

// Requirements
$manifest['requirements'] = array(
	'framework' => array(
		'min_version' => '2.5.9', // class FW_Db_Options_Model
	),
);

// Meta
$manifest['license']      = 'GPL-2.0-or-later';
$manifest['text_domain']  = 'fw';
$manifest['requires_php'] = '7.4';
$manifest['requires_wp']  = '5.8';
