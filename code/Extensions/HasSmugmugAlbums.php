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

	protected $sortField = 'Sort';

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
                    $this->owner->OrderedSmugmugAlbums(),
                    $config = \GridFieldConfig_RelationEditor::create()
                ),
                \HeaderField::create('SmugmugConfig-Title', _t('Smugmug.CONFIG', 'Configuration'), 3),
            ]
        , 'SmugmugConfig');

	    if(\ClassInfo::exists('GridFieldExtensions')) {
		    $config->removeComponentsByType('GridFieldAddExistingAutocompleter');
		    $config->addComponent(new \GridFieldAddExistingSearchButton);

		    if($this->sortField)
			    $config->addComponent(new \GridFieldOrderableRows($this->sortField));
	    }

	    if($detailForm = $config->getComponentByType('GridFieldDetailForm')) {
		    $self = $this->owner;

		    $detailForm->setItemEditFormCallback(function ($form, $controller) use ($self, $detailForm) {
			    if($this->sortField && !\ClassInfo::exists('GridFieldExtensions')) {
				    $form->Fields()->addFieldToTab('Root.Main', \NumericField::create('Sort')->setForm($form));
			    }

			    if (isset($controller->record))
				    $record = $controller->record;
			    elseif ($form->Record)
				    $record = $form->Record;
			    else
				    $record = null;

			    if ($record) {
				    foreach(array_intersect($record->many_many(), \ClassInfo::ancestry($self)) as $relation => $type) {
					    $form->Fields()->removeByName($relation);
				    }

				    $record->setEditFormWithParent($self, $form, $controller);
			    }
		    });
	    }
    }

	public function OrderedSmugmugAlbums() {
		return $this->owner->SmugmugAlbums()->sort($this->sortField, 'ASC');
	}

	public function OrderedSmugmugAlbumsWithParent($list = null) {
		if(!$list)
			$list = $this->owner->OrderedSmugmugAlbums();

		$self = $this->owner;

		return $list->each(function($item) use ($self) {
			$item->Parent = $self;
		});
	}
}