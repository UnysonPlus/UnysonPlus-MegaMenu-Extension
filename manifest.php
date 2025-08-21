<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

$manifest = array();

$manifest['name']        = __( 'Mega Menu', 'fw' );
$manifest['slug']        = 'unysonplus-megamenu';
$manifest['description'] = __( 
	'The Mega Menu extension adds a user-friendly drop down menu that will let you easily create highly customized menu configurations.', 
	'fw' 
);

$manifest['version']     = '1.1.4';
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
