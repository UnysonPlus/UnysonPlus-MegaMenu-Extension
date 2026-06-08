<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * Changelog ----------------------------------------------------------------
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

$manifest['version']     = '1.1.7';
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
