<?php
/**
 * Plugin Name:       SZM Voorraadoverzicht (compat loader)
 * Description:       Laadt SZM Woo Suite. Laat dit actief op sites waar de plugin nog onder de oude naam (szm-voorraadoverzicht/szm-voorraadoverzicht.php) geactiveerd is; nieuwe installs activeren "SZM Woo Suite".
 * Version:           1.0.15
 * Requires at least: 5.9
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * Author:            Studio Zonder Meer
 * License:           GPL-2.0-or-later
 * Text Domain:       szm-woo-suite
 */

/*
 * Compat stub, see DECISIONS.md 2026-10-02. Live sites have
 * "szm-voorraadoverzicht/szm-voorraadoverzicht.php" stored in active_plugins;
 * removing or renaming this file would silently deactivate the plugin there
 * after the next update.
 *
 * The "Version:" header above MUST equal the one in szm-woo-suite.php: on
 * those sites the updater reads the installed version from THIS file and the
 * new version from THIS file in the GitHub repo.
 *
 * No function/class declarations here (see szm-woo-suite.php guard).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'SZM_WOO_SUITE_PLUGIN_FILE' ) ) {
	define( 'SZM_WOO_SUITE_PLUGIN_FILE', __FILE__ );
}

require_once __DIR__ . '/szm-woo-suite.php';
