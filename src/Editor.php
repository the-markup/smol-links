<?php
/**
 * Class Editor
 *
 * @package   Smol Links
 * @author    The Markup
 * @license   GPL-2.0-or-later
 * @link      https://themarkup.org/
 * @copyright 2024 The Markup
 */

namespace SmolLinks;

class Editor {

	function __construct($plugin) {
		$this->plugin = $plugin;
		add_action('init', [$this, 'on_init']);
		add_action('enqueue_block_editor_assets', [$this, 'on_editor_assets']);
	}

	function on_init() {
		register_meta('post', 'smol_links_long_url', array(
			'show_in_rest' => true,
			'type' => 'string',
			'single' => true
		));
		register_meta('post', 'smol_links_short_url', array(
			'show_in_rest' => true,
			'type' => 'string',
			'single' => true
		));
		register_meta('post', 'smol_links_short_code', array(
			'show_in_rest' => true,
			'type' => 'string',
			'single' => true
		));
	}

	function on_editor_assets() {

		// Only show on 'post' post_types for now
		$screen = get_current_screen();
		if ($screen->post_type != 'post') {
			return;
		}

		$asset = include(dirname(__DIR__) . '/build/editor.asset.php');
		wp_enqueue_script(
			'smol-links-editor',
			plugins_url('build/editor.js', __DIR__),
			$asset['dependencies'],
			$asset['version']
		);

		wp_enqueue_style(
			'smol-links-editor',
			plugins_url('build/editor.css', __DIR__),
			[],
			filemtime(plugin_dir_path(__DIR__) . 'build/editor.css')
		);
	}

}
