<?php
/**
 * Milkyway Multimedia
 * SmugmugService_JSON.php
 *
 * A wrapper for the Smugmug JSON API
 *
 * @package milkyway/silverstripe-smugmug
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

class SmugmugService_JSON {
	protected $key;
	protected $nickname;

	private static $_settings;
	private static $_smugmug;

	public static function settings() {
		if(!self::$_settings)
			self::$_settings = Config::inst()->forClass('Smugmuggable');

		return self::$_settings;
	}

	public static function inst($key = '', $nickname = '') {
		if(!self::$_smugmug)
			self::$_smugmug = new SmugmugService_JSON($key, $nickname);

		return self::$_smugmug;
	}

	function __construct($key = '', $nickname = '') {
		if(!$key) $key = Session::get('SmugmugService.api_key');
		if(!$nickname) $nickname = Session::get('SmugmugService.nickname');

		$this->key = $key ? $key : $this->settings()->api_key;
		$this->nickname = $nickname ? $nickname : $this->settings()->nickname;

		if(!$this->key)
			throw new SmugmugService_Exception('Please provide an API key');

		if(!$this->ping())
			throw new SmugmugService_Exception('Could not connect to Smugmug using those credentials. Please check.');
	}

	private static $_pinged = null;

	function ping() {
		if(self::$_pinged === null) {
			try {
				$this->api('smugmug.service.ping');
				self::$_pinged = true;
			} catch (Exception $e) {
				self::$_pinged = false;
				if(Director::isDev()) throw $e;
			}
		}

		return self::$_pinged;
	}

	function categories($parent = 0, $info = false, $data = array()) {
		if($parent && !isset($data['CategoryID']))
			$data['CategoryID'] = $parent;

		$data['Heavy'] = $info ? 'true' : 'false';

		$raw = $this->api('smugmug.categories.get', $data);

		$list = ArrayList::create();

		if(isset($raw['Categories'])) {
			foreach($raw['Categories'] as $category)
				$list->push($this->arr2data($category, null, true));
		}

		return $list;
	}

	function albums($info = false, $data = array()) {
		$data['Heavy'] = $info ? 'true' : 'false';

		$raw = $this->api('smugmug.albums.get', $data);

		$list = ArrayList::create();

		if(isset($raw['Albums'])) {
			foreach($raw['Albums'] as $album) {
				if($info && isset($album['id']) && isset($album['key']))
					$list->push($this->album($album['id'], $album['key']));
				else
					$list->push($this->arr2data($album, null, true));
			}
		}

		return $list;
	}

	function album($id, $key, $data = array()) {
		$album = $this->api('smugmug.albums.getInfo', array_merge(array(
			'AlbumID' => $id,
			'AlbumKey' => $key
		), $data));

		$info = null;

		if(isset($album['Album']))
			$info = $this->arr2data($album['Album']);

		return $info;
	}

	function images($id, $key, $info = true, $size = null, $data = array()) {
		$vars = array(
			'AlbumID' => $id,
			'AlbumKey' => $key,
			'Heavy' => $info ? 'true' : 'false'
		);

		if($size) {
			if(is_array($size))
				$vars['CustomSize'] = reset($size);
			else
				$vars['CustomSize'] = $size;
		}

		$raw = $this->api('smugmug.images.get', array_merge($vars, $data));

		$list = ArrayList::create();

		if(isset($raw['Album']) && isset($raw['Album']['Images'])) {
			foreach($raw['Album']['Images'] as $image) {
				$list->push($this->arr2data($image, $size));
			}
		}

		return $list;
	}

	function image($id, $key, $size = null, $data = array()) {
		$vars = array(
			'ImageID' => $id,
			'ImageKey' => $key
		);

		if($size) {
			if(is_array($size))
				$vars['CustomSize'] = reset($size);
			else
				$vars['CustomSize'] = $size;
		}

		$raw = $this->api('smugmug.images.getInfo', array_merge($vars, $data));

		$info = null;

		if(isset($raw['Image']))
			$info = $this->arr2data($raw['Image'], $size);

		return $info;
	}

	function api($method, $data = array()) {
		$data = array_merge(array('APIKey' => $this->key, 'NickName' => $this->nickname, 'method' => $method), $data);

		$response = $this->server()->request('?' . http_build_query($data, '&'), 'GET');
		$body = $response->getBody();

		if($response->isError() || !$body)
			throw new SmugmugService_Exception('Could not connect to Smugmug');

		$data = json_decode($body, true);

		if(is_array($data) && isset($data['stat']) && $data['stat'] != 'ok')
			$this->handleError($data);
		elseif(is_array($data))
			return $data;
		else
			throw new SmugmugService_Exception('Invalid data returned from Smugmug: ' . print_r($data, true));

		return null;
	}

	public function Link() {
		return $this->settings()->json_endpoint;
	}

	private $_server;

	protected function server() {
		if(!$this->_server) {
			$expiry = $this->settings()->cache_expires ? $this->settings()->cache_expires : 86400;
			$this->_server = RestfulService::create($this->Link(), $expiry);
		}

		return $this->_server;
	}

	protected function handleError($data) {
		$msg = '';

		if(isset($data['method']))
			$msg = 'Attempted method: ' . $data['method'] . "\n";
		if(isset($data['message']))
			$msg = $data['message'] . "\n";

		throw new SmugmugService_Exception(nl2br($msg));
	}

	protected function arr2data($data = array(), $size = null) {
		$info = array();

		if(count($data)) {
			foreach($data as $key => $value) {
				if($key == 'id')
					$info['ID'] = $value;
				elseif($key == 'NiceName')
					$info['Title'] = $info['Name'] = $value;
				elseif(is_array($value)) {
					if(isset($value['id'])) {
						$relation = ucfirst($key) . 'ID';
						$info[$relation] = $value['id'];
					}

					$info[$key] = $this->arr2data($value);
				}
				elseif(!is_numeric($key))
					$info[ucfirst($key)] = $value;

				if($key == 'Caption' && filter_var($value, FILTER_VALIDATE_URL) && $url = parse_url($value)) {
					if(isset($url['host'])) {
						if(strpos($url['host'], 'youtube') !== false)
							$info['YoutubeURL'] = $value;
						elseif(strpos($url['host'], 'youtu.be') !== false)
							$info['YoutubeURL'] = $value;
						elseif(strpos($url['host'], 'vimeo') !== false)
							$info['VimeoURL'] = $value;
					}
				}

				if($size && $key == 'CustomURL') {
					if(is_array($size)) {
						$s = 'Size' . reset($size);
						$sa = key($size);

						$info[$s] = $info[$sa] = $value;
					}
					else {
						$s = 'Size' . $size;
						$info[$s] = $value;
					}
				}
			}
		}

		if(count($info))
			return ArrayData::create($info);

		return null;
	}

	public function map($method, $sort = true) {
		$map = array();

		if(method_exists($this, $method)) {
			$args = func_get_args();
			unset($args[0]);

			if(count($args))
				$list = call_user_func_array(array($this, $method), $args);
			else
				$list = $this->$method();

			if($list && $list instanceof SS_List && $list->exists()) {
				if($sort && is_array($sort))
					$list = $list->sort($sort);
				elseif($sort)
					$list = $list->sort('Title');

				foreach($list as $item)
					$map[$item->ID . '||' . $item->Key] = $item->Title;
			}
		}

		if(!count($map))
			return array('' => _t('SmugmugService_JSON.NONE_FOUND', 'No {method} available', $method));

		return $map;
	}
}

class SmugmugService_Exception extends Exception { }