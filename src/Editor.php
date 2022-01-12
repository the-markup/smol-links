<?php

namespace WP_Shlink;

class Editor {

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
			'wp-shlink-editor',
			plugins_url('build/editor.js', __DIR__),
			['wp-edit-post', 'wp-components', 'wp-plugins', 'wp-data'],
			filemtime(plugin_dir_path(__DIR__) . 'build/editor.js')
		);

		wp_enqueue_style(
			'wp-shlink-editor',
			plugins_url('build/editor.css', __DIR__),
			[],
			filemtime(plugin_dir_path(__DIR__) . 'build/editor.css')
		);
	}

}
