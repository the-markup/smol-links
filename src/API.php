<?php
/**
 * Class API
 *
 * @package   Shlink
 * @author    The Markup
 * @license   GPL-2.0-or-later
 * @link      https://themarkup.org/
 * @copyright 2022 The Markup
 */

namespace WP_Shlink;

use \WP_Shlink\Options;

/**
 * Interface to the Shlink REST API
 */
class API {

	function __construct() {
		$this->options = Options::init();
	}

	function create_shlink($request) {
		$base_url = $this->options->get('base_url');
		$endpoint = "$base_url/rest/v2/short-urls";
		return $this->request('POST', $endpoint, $request);
	}

	function update_shlink($short_code, $request) {
		$base_url = $this->options->get('base_url');
		$endpoint = "$base_url/rest/v2/short-urls/$short_code";
		return $this->request('PATCH', $endpoint, $request);
	}

	function request($method, $endpoint, $request) {
		$request = apply_filters('wp_shlink_request', $request);
		return wp_remote_request($endpoint, [
			'method'  => $method,
			'body'    => wp_json_encode($request),
			'headers' => [
				'Accept'       => 'application/json',
				'Content-Type' => 'application/json',
				'X-Api-Key'    => $this->options->get('api_key')
			]
		]);
	}

}
