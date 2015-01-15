<?php
use Milkyway\SS\Smugmug\Api\Utilities;

/**
 * Milkyway Multimedia
 * SmugmugAlbum.php
 *
 * @package milkywaymultimedia.com.au
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

class SmugmugAlbum extends DataObject {
    private static $singular_name = 'Album';

    private static $db = [
        'Title' => 'Varchar(255)',
        'SmugmugId' => 'Varchar(255)',
        'SmugmugKey' => 'Varchar(255)',
    ];

    protected $repository;
    protected $mappedRepository;

    protected function repository() {
        if(!$this->repository)
            $this->repository = Object::create('Milkyway\SS\Smugmug\Repository\ArrayDataRepository', Utilities::env_value('APIKey', $this->owner), Utilities::env_value('Nickname', $this->owner));

        return $this->repository;
    }

    protected function mappedRepository() {
        if(!$this->mappedRepository)
            $this->mappedRepository = Object::create('Milkyway\SS\Smugmug\Repository\ArrayRepository', Utilities::env_value('APIKey', $this->owner), Utilities::env_value('Nickname', $this->owner));

        return $this->mappedRepository;
    }

    public function getCMSFields() {
        $this->beforeUpdateCMSFields(function(FieldList $fields) {
                $fields->insertBefore($lists = Select2Field::create('SmugmugAlbumID', _t('Smugmug.ALBUM_FROM_SMUGMUG', 'Album from Smugmug'), '',
	                $this->mappedRepository()->albums(), null, 'Title', 'ID||Key'
                    ), 'Title');

                $lists->requireSelection = true;
                $lists->minSearchLength = 0;
                $lists->suggestURL = false;
                $lists->prefetch = 999999999999;
                $lists->sortArray = true;

                if($this->Title)
                    $fields->insertAfter(CheckboxField::create('UpdateTitleFromSmugmug', _t('Smugmug.TITLE_FROM_SMUGMUG', 'Update to use title from Smugmug')), 'Title');

                $fields->insertAfter(FormMessageField::create('SmugmugTips', _t('Smugmuggable_Album.SmugmugTips',
                            nl2br('	<strong>Note: Selecting an album will automatically generate the {id} and {key} fields.</strong>
						<strong>Tip: </strong> To add an unlisted Smugmug Album, please make the album public using the <a href="{url}" target="_blank">Smugmug Interface</a>, add the album by finding it in the listing here in the {application}, and then return to the <a href="{url}" target="_blank">Smugmug Interface</a> and make the album unlisted.'
                            ), [
                                'id' => _t('Smugmug.ID', 'Smugmug ID'),
                                'key' => _t('Smugmug.KEY', 'Smugmug Key'),
                                'url' => 'http://smugmug.com',
                                'application' => singleton('LeftAndMain')->ApplicationName,
                            ]) . '</p>')->cms(), 'SmugmugAlbumID');
            }
        );

        $fields = parent::getCMSFields();
        return $fields;
    }

    public function Images() {
        $size = null;
        $params = array();
        $var = '_images';

        $args = func_get_args();

        if(count($args) == 2) {
            $size = array($args[0] => $args[1]);
            $var .= $args[0];
        }
        elseif(count($args) == 1) {
            $size = $args[0];
            $var .= $size;
        }
        elseif(count($args)) {
            $extras = array();

            foreach($args as $param)
                $extras[] = $param;

            $params['Extras'] = implode(',', $extras);
            $var .= implode('_', $extras);
        }

        if(!isset($this->$var)) {
            try {
                $this->$var = $this->repository()->images($this->SmugmugId, $this->SmugmugKey, true, $size, $params);
            } catch(Exception $e) {
                if(Director::isDev())
                    user_error($e->getMessage());

                $this->$var = null;
            }
        }

        return $this->$var;
    }

    public function getInfo() {
        if(!isset($this->_info) && $this->SmugmugId && $this->SmugmugKey) {
            try {
                $this->_info = $this->repository()->album($this->SmugmugId, $this->SmugmugKey);
            } catch(Exception $e) {
                if(Director::isDev())
                    user_error($e->getMessage());

                $this->_info = null;
            }
        }

        return $this->_info;
    }

    private $_updating = false;

    public function getSmugmugAlbumID() {
        return ($this->SmugmugId && $this->SmugmugKey) ? $this->SmugmugId . '||' . $this->SmugmugKey : null;
    }

    public function saveSmugmugAlbumID($album = null) {
        if($album && $album != $this->SmugmugId . '||' . $this->SmugmugKey) {
            list($id, $key) = explode('||', $album);

            $this->saveSmugmugId($id);
            $this->saveSmugmugKey($key);

            if(!$this->Title && $info = $this->Info)
                $this->saveTitle($info->Title);

            $this->_updating = true;
        }
    }

    public function saveSmugmugId($data = null) {
        if(!$this->_updating && $data)
            $this->SmugmugId = $data;
    }

    public function saveSmugmugKey($data = null) {
        if(!$this->_updating && $data)
            $this->SmugmugKey = $data;
    }

    public function saveTitle($data = null) {
        if(!$this->_updating && $data)
            $this->Title = $data;
    }

    public function saveUpdateTitleFromSmugmug($do = null) {
        if(!$this->_updating && $do && $info = $this->Info)
            $this->Title = $info->Title;
    }
}