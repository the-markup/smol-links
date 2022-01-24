<?php
/**
 * Shlink
 *
 * @package   Shlink
 * @author    The Markup
 * @license   GPL-2.0-or-later
 * @link      https://themarkup.org/
 * @copyright 2022 The Markup
 *
 * @wordpress-plugin
 * Plugin Name:       Shlink
 * Description:       Create and manage Shlink short links from WordPress
 * Requires at least: 5.8
 * Requires PHP:      7.0
 * Version:           0.0.2
 * Author:            The Markup
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-shlink
 */

require_once(__DIR__ . '/src/Plugin.php');
\WP_Shlink\Plugin::init();
