<?php namespace Milkyway\SS\Smugmug\Extensions;

/**
 * Milkyway Multimedia
 * SmugmugExtension.php
 *
 * @package milkywaymultimedia.com.au
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

class HasSmugmugAlbums extends HasSmugmugConfig
{
    private static $has_one = array(
        'SmugmugConfig' => 'SmugmugConfig',
    );

    private static $many_many = array(
        'SmugmugAlbums' => 'SmugmugAlbum',
    );

    public static function get_extra_config($class, $extension, $args)
    {
        $type = isset($args[2]) ? $args[2] : $class;

        \Config::inst()->update(
            'SmugmugAlbum',
            'belongs_many_many',
            [
                $type => $class,
            ]
        );

        return null;
    }

    protected function updateFields($fields) {
        parent::updateFields($fields);

        $fields->addFieldsToTab(
            'Root.' . $this->tab,
            [
                \GridField::create(
                    'SmugmugAlbums',
                    _t('Smugmug.ALBUMS', 'Albums'),
                    $this->owner->SmugmugAlbums(),
                    \GridFieldConfig_RelationEditor::create()
                ),
                \HeaderField::create('SmugmugConfig-Title', _t('Smugmug.CONFIG', 'Configuration'), 3),
            ]
        , 'SmugmugConfig');
    }
} 