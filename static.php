<?php if (!defined('FW')) die('Forbidden');

if (is_admin()) {
	return;
}

$ext = fw_ext('megamenu');

if (!$ext) {
	return;
}

// Icon font for the front end — match whatever the configured icon picker stores.
if (apply_filters('fw:ext:megamenu:enqueue-icon-css', true)) {
	$icon_option = $ext->get_icon_option();

	if (
		$icon_option['type'] === 'icon-v2'
		&&
		($icon_v2 = fw()->backend->option_type('icon-v2'))
		&&
		isset($icon_v2->packs_loader)
		&&
		$icon_v2->packs_loader
	) {
		// Modern multi-pack icons (Font Awesome 6, etc.)
		$icon_v2->packs_loader->enqueue_frontend_css();
	} else {
		// Legacy Font Awesome 4 (bundled with the framework)
		wp_enqueue_style(
			'font-awesome',
			fw_get_framework_directory_uri('/static/libs/font-awesome/css/font-awesome.min.css'),
			array(),
			fw()->manifest->get_version()
		);
	}
}

// Baseline front-end layout (opt-out via the filter below).
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
}
