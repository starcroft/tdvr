<?php

/**
 *  qbittorrent-php - PHP QBittorrent API Wrapper
 *  Copyright (C) 2023  Aldo Barreras
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace AldoBarr;

use AldoBarr\QBittorrent\Application;
use AldoBarr\QBittorrent\Contracts\Api;
use AldoBarr\QBittorrent\Log;
use AldoBarr\QBittorrent\Sync;
use AldoBarr\QBittorrent\Torrent;
use AldoBarr\QBittorrent\Transfer;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class QBittorrent implements Api {
	public const API_VERSION_SUPPORT = '2.8.3';

	protected Client $client;
	protected string $username;
	protected string $password;
	protected string $version;
	protected bool $authenticated = false;

	public function __construct(string $url, int $port, string $username, string $password, bool $http_errors = false) {
		$parsed_url = parse_url($url);
		if (empty($parsed_url['scheme']) || empty($parsed_url['host'])) {
			throw new \AldoBarr\QBittorrent\Exceptions\InvalidUriException('Invalid URL');
		}

		$this->username = $username;
		$this->password = $password;

		$qbt = &$this;
		$stack = HandlerStack::create();
		$stack->push(Middleware::mapRequest(function(RequestInterface $request) use (&$qbt) {
			$path = $request->getUri()->getPath();
			if (strcmp(substr($path, -10), 'auth/login') === 0 || strcmp(substr($path, -11), 'auth/logout') === 0) {
				return $request;
			}

			if (!$qbt->isLoggedIn() && !$qbt->login()) {
				throw new \AldoBarr\QBittorrent\Exceptions\AuthFailedException('Unable to login to qbittorrent api');
			}

			return $request;
		}));

		$this->client = new Client([
			'cookies' => true,
			'http_errors' => $http_errors,
			'handler' => $stack,
			'base_uri' => $parsed_url['scheme'] . '://' . $parsed_url['host'] . ':' . $port . '/api/v2/'
		]);

		if (!$this->login()) {
			throw new \AldoBarr\QBittorrent\Exceptions\AuthFailedException('Unable to login to qbittorrent api');
		}

		$this->version = $this->application()->apiVersion();
		if (empty($this->version)) {
			throw new \AldoBarr\QBittorrent\Exceptions\InvalidVersionException('Your version of the qbittorrent WebAPI is not supported');
		}
	}

	public function __destruct() {
		if (!$this->logout()) {
			throw new \AldoBarr\QBittorrent\Exceptions\AuthFailedException('Unable to logout of qbittorrent api');
		}
	}

	public function addTorrent(
		?array $urls = null,
		?array $file_paths = null,
		?string $save_path = null,
		?string $cookie = null,
		?string $category = null,
		bool $skip_checking = false,
		bool $paused = false,
		?array $tags = null,
		?bool $root_folder = null,
		?string $rename = null,
		?int $up_limit = null,
		?int $down_limit = null,
		?float $ratio_limit = null,
		?int $seeding_time_limit = null,
		bool $auto_tmm = false,
		bool $sequantial_download = false,
		bool $first_last_piece_prio = false
	): bool {
		if (empty($urls) && empty($file_paths)) {
			return true;
		}

		$form_data = [
			[
				'name' => 'skip_checking',
				'contents' => $skip_checking ? 'true' : 'false'
			],
			[
				'name' => 'paused',
				'contents' => $paused ? 'true' : 'false'
			],
			[
				'name' => 'autoTMM',
				'contents' => $auto_tmm ? 'true' : 'false'
			],
			[
				'name' => 'sequentialDownload',
				'contents' => $sequantial_download ? 'true' : 'false'
			],
			[
				'name' => 'firstLastPiecePrio',
				'contents' => $first_last_piece_prio ? 'true' : 'false'
			],
		];

		if (!empty($urls)) {
			$form_data[] = [
				'name' => 'urls',
				'contents' => implode("\n", array_map('trim', $urls))
			];
		}

		if (!empty($file_paths)) {
			foreach ($file_paths as $name => $path) {
				$form_data[] = [
					'name' => 'torrents',
					'filename' => is_int($name) ? basename($path) : $name,
					'contents' => Utils::tryFopen(trim($path), 'r'),
					'headers' => ['Content-Type' => 'application/x-bittorrent']
				];
			}
		}

		if (!empty($save_path)) {
			$form_data[] = ['name' => 'savepath', 'contents' => $save_path];
		}

		if (!empty($cookie)) {
			$form_data[] = ['name' => 'cookie', 'contents' => $cookie];
		}

		if (!empty($category)) {
			$form_data[] = ['name' => 'category', 'contents' => $category];
		}

		if (!empty($tags)) {
			$form_data[] = ['name' => 'tags', 'contents' => implode(',', $tags)];
		}

		if (!is_null($root_folder)) {
			$form_data[] = ['name' => 'root_folder', 'contents' => $root_folder ? 'true' : 'false'];
		}

		if (!empty($rename)) {
			$form_data[] = ['name' => 'rename', 'contents' => $rename];
		}

		if (!is_null($up_limit)) {
			$form_data[] = ['name' => 'upLimit', 'contents' => $up_limit];
		}

		if (!is_null($down_limit)) {
			$form_data[] = ['name' => 'dlLimit', 'contents' => $down_limit];
		}

		if (!is_null($ratio_limit)) {
			$form_data[] = ['name' => 'ratioLimit', 'contents' => $ratio_limit];
		}

		if (!is_null($seeding_time_limit)) {
			$form_data[] = ['name' => 'seedingTimeLimit', 'contents' => $seeding_time_limit];
		}

		$response = $this->client->post('torrents/add', ['multipart' => $form_data]);
		return $response->getStatusCode() === 200;
	}

	public function isLoggedIn() {
		return $this->authenticated;
	}

	public function login(): bool {
		if (!$this->authenticated) {
			try {
				$response = $this->client->post('auth/login', [
					'form_params' => [
						'username' => $this->username,
						'password' => $this->password
					]
				]);

				$this->authenticated = $response->getStatusCode() === 200;
				return $this->authenticated;
			} catch (\Throwable) {}

			return false;
		}

		return true;
	}

	public function logout(): bool {
		if ($this->authenticated) {
			try {
				$response = $this->client->post('auth/logout', ['form_params' => []]);
				$this->authenticated = !($response->getStatusCode() === 200);
				return !$this->authenticated;
			} catch (\Throwable) {}

			return false;
		}

		return true;
	}

	protected function setAuthenticated(bool $authed): void {
		$this->authenticated = $authed;
	}

	public function application(): Application {
		$application = new Application;
		return $this->cloneApiObject($application);
	}

	public function log(): Log {
		$log = new Log;
		return $this->cloneApiObject($log);
	}

	public function request(string $endpoint, string $method = 'GET', array $options = []): ResponseInterface {
		unset($options['cookies']);
		return $this->client->request($method, $endpoint, $options);
	}

	public function sync(): Sync {
		$sync = new Sync;
		return $this->cloneApiObject($sync);
	}

	public function torrents(
		?string $filter = null,
		?string $category = null,
		?string $tag = null,
		?string $sort = null,
		?bool $reverse = null,
		?int $limit = null,
		?int $offset = null,
		?string $hashes = null
	): array {
		$options = [];
		$ref = new \ReflectionMethod($this, 'torrents');
		foreach ($ref->getParameters() as $param) {
			$value = ${$param->name};
			if (!is_null($value)) {
				$options[$param->name] = $value;
			}
		}

		$response = $this->client->get('torrents/info', ['query' => $options]);
		$body = $response->getBody();
		$data = json_decode($body->getContents());
		$body->close();

		return $data;
	}

	public function torrent(string $hash): Torrent {
		$torrent = new Torrent;
		$this->cloneApiObject($torrent);
		$torrent->setHash($hash);
		return $torrent;
	}

	public function transfer(): Transfer {
		$transfer = new Transfer;
		return $this->cloneApiObject($transfer);
	}

	private function &cloneApiObject(Api &$new_object): Api {
		$options = array_keys(get_object_vars($this));
		foreach ($options as $option) {
			if (strcmp($option, 'prefix') === 0) {
				continue;
			}

			$new_object->{$option} = &$this->{$option};
		}

		return $new_object;
	}
}
