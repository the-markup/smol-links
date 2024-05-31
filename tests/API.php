<?php
/**
 * Class TestAPI
 *
 * @package   Smol Links
 * @author    The Markup
 * @license   GPL-2.0-or-later
 * @link      https://themarkup.org/
 * @copyright 2024 The Markup
 */

class TestAPI extends SmolLinks\API {

	function request($method, $endpoint, $args = null) {
		$tags = isset($args['tags']) ? $args['tags'] : [];
		$domain = isset($args['domain']) ? $args['domain'] : null;
		$title = isset($args['title']) ? $args['title'] : null;
		$crawlable = isset($args['crawlable']) ? $args['crawlable'] : true;
		$forwardQuery = isset($args['forwardQuery']) ? $args['forwardQuery'] : true;

		return [
			"shortCode"      => "xxxxx",
			"shortUrl"       => "https://example.com/xxxxx",
			"longUrl"        => $args['longUrl'],
			"dateCreated"    => current_time(DATE_RFC3339),
			"visitsSummary"  => [
				"total" => 0
			],
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
	}

}
