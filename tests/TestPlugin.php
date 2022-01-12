<?php
/**
 * Class TestPlugin
 *
 * @package   Shlink
 * @author    The Markup
 * @license   GPL-2.0-or-later
 * @link      https://themarkup.org/
 * @copyright 2022 The Markup
 */

namespace WP_Shlink;

require_once(__DIR__ . '/API.php');

use \WP_Shlink\TestAPI;
use \WP_UnitTestCase;

class TestPlugin extends WP_UnitTestCase {

	function setUp() {
		// Plugin instance to test
		$this->plugin = Plugin::init();

		// Use a mocked API that simulates requests
		$this->plugin->api = new TestAPI();

		// Let's pretend we have a server config
		$this->plugin->options->set('base_url', 'not empty');
		$this->plugin->options->set('api_key', 'not empty');
	}

	function testSavePost() {

		// Test with generate option turned off
		$this->plugin->options->set('generate_on_save', false);
		$post_id = wp_insert_post([
			'post_title' => 'Testing one'
		]);
		$shlink = $this->plugin->get_post_shlink(get_post($post_id));
		$this->assertEquals($shlink, null);

		// Test with generate option turned on
		$this->plugin->options->set('generate_on_save', true);
		wp_update_post([
			'ID' => $post_id,
			'post_title' => 'Testing two'
		]);
		$shlink = $this->plugin->get_post_shlink(get_post($post_id));
		$this->assertEquals($shlink['shortCode'], 'xxxxx');
	}

}
