<?php
/**
 * Plugin Name:       SZM Woo Suite
 * Description:       Voorraadoverzicht in wp-admin: rij = product + kleur, kolom = maat, cel = voorraad / verkocht over een gekozen periode. Periode-toggle, CSV-export en een inline bewerken-modus voor eenduidig editbare cellen (precies 1 onderliggende variatie).
 * Version:           1.0.15
 * Requires at least: 5.9
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * Author:            Studio Zonder Meer
 * License:           GPL-2.0-or-later
 * Text Domain:       szm-woo-suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Load-once guard (DECISIONS.md 2026-10-02). This file is reached either
 * directly (new installs: szm-woo-suite/szm-woo-suite.php active) or via the
 * compat loader szm-voorraadoverzicht.php (sites that activated the plugin
 * under its old name). If two copies of the plugin are active (e.g. an old
 * szm-voorraadoverzicht/ folder plus a new szm-woo-suite/ folder), only the
 * first one loads.
 *
 * Keep this file free of named function/class declarations: PHP binds those
 * when the file is compiled, so the `return` below cannot prevent a
 * "Cannot redeclare" fatal for anything declared in this same file. All real
 * code lives in inc/ and is required only after the guard.
 */
if ( defined( 'SZM_WOO_SUITE_LOADED' ) ) {
	if ( SZM_WOO_SUITE_LOADED !== __FILE__ ) {
		$GLOBALS['szm_woo_suite_skipped_copies'][] = __FILE__;
	}
	return;
}
define( 'SZM_WOO_SUITE_LOADED', __FILE__ );

// The file WordPress has active. The compat loader defines it first, so on
// legacy sites this is szm-voorraadoverzicht/szm-voorraadoverzicht.php.
if ( ! defined( 'SZM_WOO_SUITE_PLUGIN_FILE' ) ) {
	define( 'SZM_WOO_SUITE_PLUGIN_FILE', __FILE__ );
}

// Release: bump this AND the "Version:" header in BOTH szm-woo-suite.php and
// szm-voorraadoverzicht.php (the updater reads the header of whichever file
// is active on a site, see README "Updates").
define( 'SZM_WOO_SUITE_VERSION', '1.0.15' );
define( 'SZM_VOORRAAD_VERSION', SZM_WOO_SUITE_VERSION ); // pre-rename name, kept for compat.

/*
 * Update source. The GitHub repo is still named szm-voorraadoverzicht.
 * After `gh repo rename szm-woo-suite`, change ONLY this line to
 * 'https://github.com/Yelbow/szm-woo-suite'.
 */
define( 'SZM_WOO_SUITE_REPO_URL', 'https://github.com/Yelbow/szm-voorraadoverzicht' );

/*
 * Plugin Update Checker slug: an internal key (stored state option
 * "external_updates-<slug>", hook names, "View details" link). It is NOT the
 * repo name and NOT the folder name; PUC keeps the existing folder name on
 * update regardless. Kept at the pre-rename value so sites keep their cached
 * update state. No need to change it after the repo rename.
 */
define( 'SZM_WOO_SUITE_UPDATE_SLUG', 'szm-voorraadoverzicht' );

/**
 * Self-updates through WordPress's native Plugins/Updates screen, no
 * separate updater plugin needed on client sites. Checks the GitHub repo
 * for new releases/tags and shows the normal "Update available" notice.
 */
require_once __DIR__ . '/inc/plugin-update-checker/plugin-update-checker.php';
add_action( 'init', function () {
	$update_checker = \YahnisElsts\PluginUpdateChecker\v5p4\PucFactory::buildUpdateChecker(
		SZM_WOO_SUITE_REPO_URL,
		SZM_WOO_SUITE_PLUGIN_FILE,
		SZM_WOO_SUITE_UPDATE_SLUG
	);
	$update_checker->setBranch( 'main' );
	// If the repo is private, uncomment and set a fine-grained,
	// read-only-on-this-repo GitHub access token:
	// $update_checker->setAuthentication( 'ghp_xxxxxxxxxxxxxxxxxxxx' );
} );

/**
 * WooCommerce is a hard requirement (wc_get_orders(), wc_get_product(), ...
 * throughout this plugin). The "Requires Plugins" header already blocks
 * activation on a site without it (WP 6.5+), TGM additionally nudges an
 * admin to install/activate it on older WP versions where that header is
 * ignored.
 */
require_once __DIR__ . '/inc/tgm-plugin-activation/class-tgm-plugin-activation.php';
add_action( 'tgmpa_register', function () {
	tgmpa(
		array(
			array(
				'name'     => 'WooCommerce',
				'slug'     => 'woocommerce',
				'required' => true,
			),
		),
		array(
			'id'           => 'szm-woo-suite',
			'menu'         => 'szm-woo-suite-install-plugins',
			'has_notices'  => true,
			'is_automatic' => false,
		)
	);
} );

/*
 * The plugin folder holds two files with a plugin header (this one and the
 * compat loader), so WordPress lists two plugins for one folder. Hide the
 * inactive sibling of the file that is actually active, so nobody activates
 * it "to be sure". Only touches the Plugins list, not get_plugins().
 */
add_filter( 'all_plugins', function ( $plugins ) {
	$entry = plugin_basename( SZM_WOO_SUITE_PLUGIN_FILE );
	$dir   = dirname( $entry );
	foreach ( array( 'szm-woo-suite.php', 'szm-voorraadoverzicht.php' ) as $file ) {
		$sibling = $dir . '/' . $file;
		if ( $sibling !== $entry && isset( $plugins[ $sibling ] ) && ! is_plugin_active( $sibling ) ) {
			unset( $plugins[ $sibling ] );
		}
	}
	return $plugins;
} );

// A second active copy was skipped by the guard above: say so instead of
// failing silently.
add_action( 'admin_notices', function () {
	if ( empty( $GLOBALS['szm_woo_suite_skipped_copies'] ) || ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	echo '<div class="notice notice-warning"><p>SZM Woo Suite is twee keer actief. Gebruikt: <code>'
		. esc_html( plugin_basename( SZM_WOO_SUITE_PLUGIN_FILE ) )
		. '</code>. Overgeslagen: <code>'
		. esc_html( implode( ', ', array_map( 'plugin_basename', $GLOBALS['szm_woo_suite_skipped_copies'] ) ) )
		. '</code>. Deactiveer de overgeslagen kopie.</p></div>';
} );

// Modules.
require_once __DIR__ . '/inc/voorraadoverzicht.php';
