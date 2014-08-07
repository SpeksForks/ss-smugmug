<?php namespace Milkyway\SS\Smugmug\Repository;
/**
 * Milkyway Multimedia
 * Parse.php
 *
 * @package milkywaymultimedia.com.au
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */


class ArrayDataRepository {
    protected $smugmug;

    public function __construct($key, $nickname, $cache = 6) {
        $this->smugmug = \Injector::inst()->createWithArgs('Milkyway\SS\Smugmug\Api\JSON', [$key, $nickname, $cache]);
    }

    public function categories($parent = 0, $withInfo = false, $settings = []) {
        $all = [];

        $entries = $this->smugmug->categories($parent, $withInfo, $settings);

        foreach($entries as $entry)
            $all[] = $this->convertToArrayData($entry);

        return \ArrayList::create($all);
    }

    public function albums($withInfo = false, $settings = []) {
        $all = [];

        $entries = $this->smugmug->albums($withInfo, $settings);

        foreach($entries as $entry)
            $all[] = $this->convertToArrayData($entry);

        return \ArrayList::create($all);
    }

    public function album($id, $key, $settings = []) {
        $response = $this->smugmug->album($id, $key, $settings);
        return \ArrayData::create($this->convertToArrayData($response));
    }

    public function images($id, $key, $withInfo = true, $size = null, $settings = []) {
        $all = [];

        $entries = $this->smugmug->images($id, $key, $withInfo, $size, $settings);

        foreach($entries as $entry)
            $all[] = $this->convertToArrayData($entry, $size);

        return \ArrayList::create($all);
    }

    public function image($id, $key, $size = null, $settings = []) {
        $response = $this->smugmug->image($id, $key, $size, $settings);
        return \ArrayData::create($this->convertToArrayData($response, $size));
    }

    protected function convertToArrayData(&$data, $size = null) {
        foreach($data as $key => $item) {
            if(is_array($item)) {
                if(!\ArrayLib::is_associative($item)) {
                    $this->convertToArrayData($item, $size);
                    $data[$key] = \ArrayList::create($item);
                }
                else {
                    if(isset($item['id']))
                        $data[ucfirst($key) . 'ID'] = $item['id'];

                    $data[$key] = \ArrayData::create($item);
                }
            }
            else {
                if($key == 'id')
                    $data['ID'] = $item;
                elseif($key == 'NiceName')
                    $data['Title'] = $data['Name'] = $item;
                elseif($key == 'Caption' && filter_var($item, FILTER_VALIDATE_URL) && $url = parse_url($item)) {
                    if(isset($url['host'])) {
                        if(strpos($url['host'], 'youtube') !== false)
                            $info['YoutubeURL'] = $item;
                        elseif(strpos($url['host'], 'youtu.be') !== false)
                            $info['YoutubeURL'] = $item;
                        elseif(strpos($url['host'], 'vimeo') !== false)
                            $info['VimeoURL'] = $item;
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