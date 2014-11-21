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

	protected $sortField;

	public function __construct($tab = 'Smugmug', $useCMSFieldsAlways = false, $albumRelation = '', $sortField = 'Sort') {
		parent::__construct($tab, $useCMSFieldsAlways);
		$this->sortField = $sortField;
	}

    public static function get_extra_config($class, $extension, $args)
    {
        $type = isset($args[2]) ? $args[2] : $class;
	    $sortField = isset($args[3]) ? $args[3] : 'Sort';

        \Config::inst()->update(
            'SmugmugAlbum',
            'belongs_many_many',
            [
                $type => $class,
            ]
        );

        return [
	        'many_many_extraFields' => [
		        'SmugmugAlbums' => [
			        $sortField => 'Int',
		        ],
	        ],
        ];
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
                    $config = \GridFieldConfig_RelationEditor::create()
                ),
                \HeaderField::create('SmugmugConfig-Title', _t('Smugmug.CONFIG', 'Configuration'), 3),
            ]
        , 'SmugmugConfig');

	    if(class_exists('GridFieldExtensions')) {
		    $config->removeComponentsByType('GridFieldAddExistingAutocompleter');
		    $config->addComponent(new \GridFieldAddExistingSearchButton);

		    if($this->sortField)
			    $config->addComponent(new \GridFieldOrderableRows($this->sortField));
	    }
    }
} 