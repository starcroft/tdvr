<?php

namespace AldoBarr\QBittorrent;

use AldoBarr\QBittorrent;

class Sync extends QBittorrent {
	protected string $prefix = 'sync/';

	protected function __construct() {}

	public function maindata(int $rid = 0): object {
		$response = $this->client->get($this->prefix . 'maindata', [
			'query' => [
				'rid' => $rid
			]
		]);

		$body = $response->getBody();
		$data = json_decode($body->getContents());
		$body->close();

		return $data;
	}

	public function torrentPeers(string $hash, int $rid = 0): object {
		$response = $this->client->get($this->prefix . 'torrentPeers', [
			'query' => [
				'hash' => $hash,
				'rid' => $rid
			]
		]);

		$body = $response->getBody();
		$data = json_decode($body->getContents());
		$body->close();

		return $data;
	}
}
