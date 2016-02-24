<?php namespace Milkyway\SS\Smugmug\Repository;
/**
 * Milkyway Multimedia
 * Parse.php
 *
 * @package milkywaymultimedia.com.au
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */


class ArrayRepository extends Base {
    public function categories($parent = 0, $withInfo = false, $settings = []) {
        $all = [];

        $entries = $this->smugmug->categories($parent, $withInfo, $settings);

        foreach($entries as $entry) {
            $this->remap($entry);
            $all[] = $entry;
        }

        return $all;
    }

    public function albums($withInfo = false, $settings = []) {
        $all = [];

        $entries = $this->smugmug->albums($withInfo, $settings);

        foreach($entries as $entry) {
            $this->remap($entry);
            $all[] = $entry;
        }

        return $all;
    }

    public function album($id, $key, $settings = []) {
        $response = $this->smugmug->album($id, $key, $settings);
        $this->remap($response);
        return $response;
    }

    public function images($id, $key, $withInfo = true, $size = null, $settings = []) {
        $all = [];

        $entries = $this->smugmug->images($id, $key, $withInfo, $size, $settings);

        foreach($entries as $entry) {
            $this->remap($entry);
            $all[] = $entry;
        }

        return $all;
    }

    public function image($id, $key, $size = null, $settings = []) {
        $response = $this->smugmug->image($id, $key, $size, $settings);
        $this->remap($response);
        return $response;
    }

    protected function remap(&$data, $size = null) {
        foreach($data as $key => $item) {
            if(is_array($item)) {
                if(!\ArrayLib::is_associative($item)) {
                    $this->remap($item, $size);
                    $data[$key] = $item;
                }
                else {
                    if(isset($item['id']))
                        $data[ucfirst($key) . 'ID'] = $item['id'];

                    $data[$key] = $item;
                }
            }
            else {
                if($key == 'id')
                    $data['ID'] = $item;
                elseif($key == 'NiceName')
                    $data['Title'] = $data['Name'] = $item;
                elseif($key == 'Caption' && filter_var($item, FILTER_VALIDATE_URL) && $url = parse_url($item)) {
                    if(isset($url['host'])) {
                        $item = urldecode($item);

                        if(strpos($url['host'], 'youtube') !== false)
	                        $data['YoutubeURL'] = $item;
                        elseif(strpos($url['host'], 'youtu.be') !== false)
	                        $data['YoutubeURL'] = $item;
                        elseif(strpos($url['host'], 'vimeo') !== false)
	                        $data['VimeoURL'] = $item;

	                    if(isset($data['YoutubeURL']) && isset($url['query'])) {
							$params = [];
		                    parse_str($url['query'], $params);

		                    if(isset($params['v']))
			                    $data['YoutubeId'] = $params['v'];
	                    }
                    }
                }
                elseif($key == 'CustomURL' && $size) {
                    if(is_array($size))
                        $data['Size' . reset($size)] = $data[key($size)] = $item;
                    else
                        $data['Size' . $size] = $item;
                }
            }
        }
    }
}