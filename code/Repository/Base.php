<?php
/**
 * Milkyway Multimedia
 * Base.php
 *
 * @package milkywaymultimedia.com.au
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

namespace Milkyway\SS\Smugmug\Repository;


abstract class Base {
    protected $smugmug;

    public function __construct($key, $nickname, $cache = 6) {
        $this->smugmug = \Injector::inst()->createWithArgs('Milkyway\SS\Smugmug\Api\JSON', [$key, $nickname, $cache]);
    }
} 