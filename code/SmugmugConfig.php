<?php

/**
 * Milkyway Multimedia
 * SmugmugConfig.php
 *
 * @package milkywaymultimedia.com.au
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */
class SmugmugConfig extends DataObject
{
    private static $db = [
        'APIKey'   => 'Text',
        'Nickname' => 'Varchar',
    ];
} 