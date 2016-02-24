<?php namespace Milkyway\SS\Smugmug\Repository;
/**
 * Milkyway Multimedia
 * Parse.php
 *
 * @package milkywaymultimedia.com.au
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */


class ArrayDataRepository extends ArrayRepository {
    public function categories($parent = 0, $withInfo = false, $settings = []) {
        $all = [];

        $entries = parent::categories($parent, $withInfo, $settings);

        foreach($entries as $entry) {
            $this->convertToArrayData($entry);
            $all[] = \ArrayData::create($entry);
        }

        return \ArrayList::create($all);
    }

    public function albums($withInfo = false, $settings = []) {
        $all = [];

        $entries = parent::albums($withInfo, $settings);

        foreach($entries as $entry) {
            $this->convertToArrayData($entry);
            $all[] = \ArrayData::create($entry);
        }

        return \ArrayList::create($all);
    }

    public function album($id, $key, $settings = []) {
        $response = parent::album($id, $key, $settings);
        $this->convertToArrayData($response);
        return \ArrayData::create($response);
    }

    public function images($id, $key, $withInfo = true, $size = null, $settings = []) {
        $all = [];

        $entries = parent::images($id, $key, $withInfo, $size, $settings);

        foreach($entries as $entry) {
            $this->convertToArrayData($entry);
            $all[] = \ArrayData::create($entry);
        }

        return \ArrayList::create($all);
    }

    public function image($id, $key, $size = null, $settings = []) {
        $response = parent::image($id, $key, $settings);
        $this->convertToArrayData($response);
        return \ArrayData::create($response);
    }

    protected function convertToArrayData(&$data) {
        foreach($data as $key => $item) {
            if(is_array($item)) {
                if(!\ArrayLib::is_associative($item)) {
                    $this->convertToArrayData($item);
                    $data[$key] = \ArrayList::create($item);
                }
                else {
                    $data[$key] = \ArrayData::create($item);
                }
            }
        }
    }
}