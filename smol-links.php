<?php
/**
 * Smol Links
 *
 * @package   Smol Links
 * @author    The Markup
 * @license   GPL-2.0-or-later
 * @link      https://themarkup.org/
 * @copyright 2022 The Markup
 *
 * @wordpress-plugin
 * Plugin Name:       Smol Links
 * Description:       Create and manage Shlink short links from WordPress
 * Requires at least: 4.5
 * Requires PHP:      7.0
 * Version:           0.0.1
 * Author:            The Markup
 * Author URI:        https://themarkup.org/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       smol-links
 */

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
	require_once(__DIR__ . '/vendor/autoload.php');
}

add_action('plugins_loaded', function() {
	global $smol_links_plugin;
	$smol_links_plugin = new SmolLinks\Plugin();
});
