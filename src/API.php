<?php

namespace WP_Shlink;

class API {

	function __construct() {
		$this->options = \WP_Shlink\Options::init();
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
