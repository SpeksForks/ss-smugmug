<?php namespace Milkyway\SS\Smugmug\Extensions;

/**
 * Milkyway Multimedia
 * SmugmugExtension.php
 *
 * @package milkywaymultimedia.com.au
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

class HasSmugmugAlbums extends \DataExtension
{
    private static $has_one = array(
        'SmugmugConfig' => 'SmugmugConfig',
    );

    private static $many_many = array(
        'SmugmugAlbums' => 'SmugmugAlbum',
    );

    public static function get_extra_config($class, $extension, $args)
    {
        $type = isset($args[1]) ? $args[1] : $class;

        \Config::inst()->update(
            'SmugmugAlbum',
            'belongs_many_many',
            [
                $type => $class,
            ]
        );

        return null;
    }

    protected $useCMSFieldsAlways;

    public function __construct($useCMSFieldsAlways = false) {
        parent::__construct();
        $this->useCMSFieldsAlways = $useCMSFieldsAlways;
    }

    function updateCMSFields(\FieldList $fields)
    {
        if (!$this->useCMSFieldsAlways && ($this->owner instanceof \SiteTree)) {
            return;
        }

        $fields->addFieldsToTab(
            'Root.Smugmug', [
                \HasOneCompositeField::create('SmugmugConfig'),
                \GridField::create(
                    'SmugmugAlbums',
                    _t('Smugmug.ALBUMS', 'Albums'),
                    $this->owner->SmugmugAlbums(),
                    \GridFieldConfig_RelationEditor::create()
                ),
            ]
        );
    }

    function updateSettingsFields($fields)
    {
        if (!$this->useCMSFieldsAlways && ($this->owner instanceof \SiteTree)) {
            $fields->addFieldsToTab(
                'Root.Smugmug',
                [
                    \HasOneCompositeField::create('SmugmugConfig'),
                    \GridField::create(
                        'SmugmugAlbums',
                        _t('Smugmug.ALBUMS', 'Albums'),
                        $this->owner->SmugmugAlbums(),
                        \GridFieldConfig_RelationEditor::create()
                    ),
                ]
            );
        }
    }
} 