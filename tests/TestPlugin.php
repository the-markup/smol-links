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

require_once(__DIR__ . '/API.php');

class TestPlugin extends WP_UnitTestCase {

	function setUp() {
		global $shlinkify_plugin;
		$this->plugin = $shlinkify_plugin;
		$this->plugin->api = new TestAPI($this->plugin);

		// Let's pretend we have a server config
		$this->plugin->options->set('base_url', 'not empty');
		$this->plugin->options->set('api_key', 'not empty');
	}

	function testSavePost() {
		// Test with generate option turned off
		$this->plugin->options->set('generate_on_save', false);
		$post_id = $this->factory->post->create();
		$shlink = $this->plugin->get_post_shlink(get_post($post_id));
		$this->assertEquals($shlink, null);

		// Test with generate option turned on
		$this->plugin->options->set('generate_on_save', true);
		wp_update_post([
			'ID' => $post_id,
			'post_title' => 'Testing generate_on_save'
		]);
		$shlink = $this->plugin->get_post_shlink(get_post($post_id));
		$this->assertEquals($shlink['short_code'], 'xxxxx');
	}

}
