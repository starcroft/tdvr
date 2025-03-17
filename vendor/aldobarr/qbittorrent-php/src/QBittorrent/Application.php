<?php

namespace AldoBarr\QBittorrent;

use AldoBarr\QBittorrent;

class Application extends QBittorrent {
	protected string $prefix = 'app/';

	protected function __construct() {}

	public function version(): string {
		try {
			$response = $this->client->get($this->prefix . 'version');
			$body = $response->getBody();
			$version = $body->getContents();
			$body->close();

			if (strcasecmp(substr($version, 0, 1), 'v') === 0) {
				$version = substr($version, 1);
			}

			return $version;
		} catch (\Throwable) {}

		return '';
	}

	public function apiVersion(): string {
		try {
			$response = $this->client->get($this->prefix . 'webapiVersion');
			$body = $response->getBody();
			$version = $body->getContents();
			$body->close();

			if (strcasecmp(substr($version, 0, 1), 'v') === 0) {
				$version = substr($version, 1);
			}

			return $version;
		} catch (\Throwable) {}

		return '';
	}

	public function buildInfo(): object {
		$response = $this->client->get($this->prefix . 'webapiVersion');
		$body = $response->getBody();
		$data = json_decode($body->getContents());
		$body->close();

		return $data;
	}

	public function shutdown(): bool {
		$response = $this->client->post($this->prefix . 'shutdown');
		$success = $response->getStatusCode() === 200;
		if ($success) {
			$this->setAuthenticated(false);
		}

		return $success;
	}

	public function preferences(): object {
		$response = $this->client->get($this->prefix . 'preferences');
		$body = $response->getBody();
		$data = json_decode($body->getContents());
		$body->close();

		return $data;
	}

	public function setPreferences(object $preferences): bool {
		$body = 'json=' . json_encode($preferences);
		$response = $this->client->post($this->prefix . 'setPreferences', [
			'headers' => [
				'Content-Type' => 'application/x-www-form-urlencoded',
				'Content-Length' => strlen($body)
			],
			'body' => $body
		]);

		return $response->getStatusCode() === 200;
	}

	public function getDefaultSavePath(): string {
		$response = $this->client->get($this->prefix . 'defaultSavePath');
		$body = $response->getBody();
		$path = $body->getContents();
		$body->close();

		return $path;
	}
}
