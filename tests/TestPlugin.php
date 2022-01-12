<?php
/**
 * Class TestPlugin
 *
 * @package wp-shlink
 */

namespace WP_Shlink;

require_once dirname(__DIR__) . '/src/API.php';

use \WP_Shlink\API;
use \WP_UnitTestCase;

class TestAPI extends API {

	function request($method, $endpoint, $request) {

		$tags = isset($request['tags']) ? $request['tags'] : [];
		$domain = isset($request['domain']) ? $request['domain'] : null;
		$title = isset($request['title']) ? $request['title'] : null;
		$crawlable = isset($request['crawlable']) ? $request['crawlable'] : true;
		$forwardQuery = isset($request['forwardQuery']) ? $request['forwardQuery'] : true;

		$response = [
			"shortCode"      => "xxxxx",
			"shortUrl"       => "https://example.com/xxxxx",
			"longUrl"        => $request['longUrl'],
			"dateCreated"    => current_time(DATE_RFC3339),
			"visitsCount"    => 0,
			"tags"           => $tags,
			"meta"           => [
				"validSince" => null,
				"validUntil" => null,
				"maxVisits"  => null
			],
			"domain"         => $domain,
			"title"          => $title,
			"crawlable"      => $crawlable,
			"forwardQuery"   => $forwardQuery
		];

		return [
			'response' => [
				'code' => 200,
				'message' => 'OK'
			],
			'body' => json_encode($response)
		];
	}

}

class TestPlugin extends WP_UnitTestCase {

	function setUp() {
		// Plugin instance to test
		$this->plugin = Plugin::init();

		// Use a mocked API that simulates requests
		$this->plugin->api = new TestAPI();

		// Let's pretend we have Shlink options setup properly
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
