<?php namespace Milkyway\SS\Smugmug\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Message\ResponseInterface;

/**
 * Milkyway Multimedia
 * JSON.php
 *
 * @package reggardocolaianni.com
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

class JSON {
    const VERSION = '1.3.0';

    private static $_pinged = null;

    protected $endpoint = 'http://api.smugmug.com/services/api/json';
    protected $method = 'get';
    protected $client;
    protected $cache;

    protected $key;
    protected $nickname;
    protected $cacheLifetime = 6;

    public function __construct($key, $nickname, $cache = 6) {
        $this->key = $key;
        $this->nickname = $nickname;
        $this->cacheLifetime = $cache;

        $this->ping();
    }

    public function ping() {
        if(self::$_pinged === null) {
            $this->request('smugmug.service.ping');
            self::$_pinged = true;
        }

        return self::$_pinged;
    }

    public function categories($parent = 0, $withInfo = false, $settings = []) {
        if($parent && !isset($settings['CategoryID']))
            $settings['CategoryID'] = $parent;

        $settings['Heavy'] = $withInfo ? 'true' : 'false';

        $data = $this->request('smugmug.categories.get', $settings);

        if(isset($data['Categories']))
            return $data['Categories'];

        return [];
    }

    public function albums($withInfo = false, $settings = []) {
        $settings['Heavy'] = $withInfo ? 'true' : 'false';

        $data = $this->request('smugmug.albums.get', $settings);

        if(isset($data['Albums']))
            return $data['Albums'];

        return [];
    }

    public function album($id, $key, $settings = []) {
        $data = $this->request('smugmug.albums.getInfo', array_merge([
                    'AlbumID' => $id,
                    'AlbumKey' => $key
                ],
                $settings
            )
        );

        if(isset($data['Album']))
            return $data['Album'];

        return [];
    }

    public function images($id, $key, $withInfo = true, $size = null, $settings = []) {
        $vars = [
            'AlbumID' => $id,
            'AlbumKey' => $key,
            'Heavy' => $withInfo ? 'true' : 'false'
        ];

        if($size) {
            if(is_array($size))
                $vars['CustomSize'] = reset($size);
            else
                $vars['CustomSize'] = $size;
        }

        $data = $this->request('smugmug.images.get', array_merge($vars, $settings));

        if(isset($data['Album']) && isset($data['Album']['Images']))
            return $data['Album']['Images'];

        return [];
    }

    public function image($id, $key, $size = null, $settings = []) {
        $vars = [
            'ImageID' => $id,
            'ImageKey' => $key,
        ];

        if($size) {
            if(is_array($size))
                $vars['CustomSize'] = reset($size);
            else
                $vars['CustomSize'] = $size;
        }

        $data = $this->request('smugmug.images.getInfo', array_merge($vars, $settings));

        if(isset($data['Image']))
            return $data['Image'];

        return [];
    }

    public function request($action, $settings = [], $cache = true) {
        $settings = array_merge(['APIKey' => $this->key, 'NickName' => $this->nickname, 'method' => $action], $settings);
        $cacheKey = $this->getCacheKey($settings);

        if(isset($_GET['flush']) || isset($_GET['smugmug']) || !$cache || !($body = unserialize($this->cache()->load($cacheKey)))) {
            try {
                $response = $this->http()->{$this->method}(
                    $this->endpoint(),
                    ['query' => $settings]
                );
            } catch(RequestException $e) {
                if(($response = $e->getResponse()) && $body = $this->parseResponse($response)) {
                    throw new JSON_Exception($response, isset($body['name']) ? $body['name'] : '', isset($body['code']) ? $body['code'] : 400);
                }
            }

            if($response && !$this->isError($response)) {
                $body = $this->parseResponse($response);

                if(!$this->isValid($body))
                    throw new JSON_Exception($response, sprintf('Data not received from %s. Please check your credentials.', $this->endpoint));

                $this->cache()->save(serialize($body), $cacheKey);

                return $body;
            }
        }

        return $body;
    }

    /**
     * Get a new HTTP client instance.
     * @return \GuzzleHttp\Client
     */
    protected function http()
    {
        if(!$this->client)
            $this->client = new Client($this->getHttpSettings());

        return $this->client;
    }

    protected function getHttpSettings() {
        return [
            'base_url' => $this->endpoint,
        ];
    }

    protected function isError(ResponseInterface $response) {
        return ($response->getStatusCode() < 200 || $response->getStatusCode() > 399);
    }

    protected function cache() {
        if(!$this->cache)
            $this->cache = \SS_Cache::factory('SocialFeed_Providers', 'Output', ['lifetime' => $this->cacheLifetime * 60 * 60]);

        return $this->cache;
    }

    protected function getCacheKey(array $vars = []) {
        return preg_replace('/[^a-zA-Z0-9_]/', '', get_class($this) . '_' . urldecode(http_build_query($vars, '', '_')));
    }

    protected function parseResponse(ResponseInterface $response) {
        return $response->json();
    }

    protected function isValid($body) {
        return true;
    }

    protected function endpoint($action = '') {
        return \Controller::join_links($this->endpoint, static::VERSION, $action);
    }
}

class JSON_Exception extends \Exception {
    public $response;

    public function __construct($response = null, $message = null, $statusCode = null, $statusDescription = null) {
        parent::__construct($message, $statusCode, $statusDescription);
        $this->response = $response;
    }
}