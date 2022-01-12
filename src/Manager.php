<?php

namespace WP_Shlink;

use WP_Shlink\API;

class Manager {

	function __construct() {
		$this->api = API::init();
		add_action('admin_menu', [$this, 'on_admin_menu']);
		add_action('admin_enqueue_scripts', [$this, 'on_enqueue_assets']);
		add_action('wp_ajax_shlinks', [$this, 'ajax_shlinks']);
	}

	function on_admin_menu() {
		add_menu_page(
			__('Shlink Manager', 'wp-shlink'),
			__('Shlinks', 'wp-shlink'),
			'edit_posts',
			'shlinks',
			[$this, 'manager_page'],
			'dashicons-admin-links',
			20
		);
	}

	function on_enqueue_assets($suffix) {
		if ($suffix != 'toplevel_page_shlinks') {
			return;
		}

		wp_enqueue_script(
			'wp-shlink-manager',
			plugins_url('build/manager.js', __DIR__),
			['jquery'],
			filemtime(plugin_dir_path(__DIR__) . 'build/manager.js')
		);

		wp_enqueue_style(
			'wp-shlink-manager',
			plugins_url('build/manager.css', __DIR__),
			[],
			filemtime(plugin_dir_path(__DIR__) . 'build/manager.css')
		);
	}

	function manager_page() {
		?>
		<div class="wrap">
			<h1><?php _e('Shlink Manager', 'wp-shlink'); ?></h1>

			<div class="shlink-manager">Loading...</div>
		</div>
		<?php
	}

	function ajax_shlinks() {
		try {
			$shlinks = $this->api->get_shlinks([
				'page'         => 1,
				'itemsPerPage' => 25,
				'orderBy'      => 'dateCreated-DESC'
			]);

			header('Content-Type: application/json');
			echo json_encode([
				'ok' => true,
				'shlinks' => $shlinks
			]);
		} catch (Exception $error) {
			header('Content-Type: application/json');
			echo json_encode([
				'ok' => false,
				'error' => $error->getMessage()
			]);
		}

		exit;
	}

}
