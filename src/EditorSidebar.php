<?php

namespace WP_Shlink;

class EditorSidebar {

	function __construct() {
		add_action('init', [$this, 'on_init']);
		add_action('enqueue_block_editor_assets', [$this, 'on_editor_assets']);
	}

	function on_init() {
		register_meta('post', 'shlink', array(
			'show_in_rest' => true,
			'type' => 'string',
			'single' => true,
		));
	}

	function on_editor_assets() {

		// Only show on 'post' post_types for now
		$screen = get_current_screen();
		if ($screen->post_type != 'post') {
			return;
		}

		wp_enqueue_script(
			'wp-shlink',
			plugins_url('build/main.js', __DIR__),
			['wp-edit-post', 'wp-components', 'wp-plugins', 'wp-data'],
			filemtime(plugin_dir_path(__DIR__) . 'build/main.js')
		);

		wp_enqueue_style(
			'wp-shlink',
			plugins_url('build/main.css', __DIR__),
			[],
			filemtime(plugin_dir_path(__DIR__) . 'build/main.css')
		);
	}

}
