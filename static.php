<?php if (!defined('FW')) die('Forbidden');

if (is_admin()) {
	return;
}

$ext = fw_ext('megamenu');

if (!$ext) {
	return;
}

// Icon font for the front end — match whatever the configured icon picker stores.
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

	// Front-end behavior config. Theme-agnostic defaults; the host theme (or any
	// integration) feeds real values via the filter — e.g. UnysonPlus theme maps
	// its "Header → Mega Menu → Animation & Behavior" settings here.
	wp_localize_script(
		'fw-ext-megamenu',
		'_fw_mega_menu',
		/** Filters the mega menu front-end behavior config localized to script, e.g. whether submenus open on hover or click. */
		apply_filters('fw:ext:megamenu:frontend-config', array(
			'openOn' => 'hover', // 'hover' | 'click'
		))
	);
}
