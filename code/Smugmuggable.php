<?php
/**
 * Milkyway Multimedia
 * Smugmuggable.php
 *
 * @package
 * @author Mellisa Hankins <mellisa.hankins@me.com>
 */

class Smugmuggable extends DataExtension {
	private static $db = array(
		'Smugmug_APIKey' => 'Text',
		'Smugmug_Nickname' => 'Varchar(255)',
	);

	private static $many_many = array(
		'Smugmug_Albums' => 'Smugmuggable_Album',
	);

	private static $field_labels = array(
		'Smugmug_APIKey' => 'API Key',
		'Smugmug_Nickname' => 'Nickname',
		'Smugmug_Albums' => 'Albums',
	);

	function updateSettingsFields($fields) {
		$fields->addFieldToTab('Root.Smugmug',
			TextField::create('Smugmug_APIKey', $this->owner->fieldLabel('Smugmug_APIKey'))
				->setAttribute('placeholder', SmugmugService_JSON::settings()->api_key),
			TextField::create('Smugmug_Nickname', $this->owner->fieldLabel('Smugmug_Nickname'))
				->setAttribute('placeholder', SmugmugService_JSON::settings()->nickname),
			GridField::create('Smugmug_Albums', $this->owner->fieldLabel('Smugmug_Albums'), $this->owner->Smugmug_Albums(), GridFieldConfig_RelationEditor::create())
		);
	}
}

class Smugmuggable_Album extends DataObject {
	private static $singular_name = 'Smugmug Album';

	private static $db = array(
		'Title' => 'Varchar(255)',
		'SmugmugID' => 'Varchar(255)',
		'SmugmugKey' => 'Varchar(255)',
	);

	private static $field_labels = array(
		'SmugmugID' => 'ID (Smugmug)',
		'SmugmugKey' => 'Key (Smugmug)',
		'UpdateTitle' => 'Update Title to match album name from Smugmug?',
		'ManualEntry' => '(Enter details manually - or select from below)',
	);

	public function getCMSFields() {
		$this->beforeUpdateCMSFields(function(FieldList $fields) {
			$dataFields = $fields->dataFields();
			$first = reset($dataFields);

			if($first && ($albums = SmugmugService_JSON::inst()->map('albums'))) {
				$fields->insertBefore(
					$d = DropdownField::create('SmugmugAlbum', $this->fieldLabel('SmugmugAlbum'), $albums)->setValue($this->SmugmugID . '||' . $this->SmugmugKey)
					, $first->Name);

				if($this->SmugmugID && $this->SmugmugKey && !isset($albums[$this->SmugmugID . '||' . $this->SmugmugKey]))
					$d->setEmptyString($this->Title . ' (' . $this->fieldLabel('Unlisted') . ')');
				elseif($this->SmugmugID && $this->SmugmugKey)
					$d->setEmptyString($this->Title);
				else
					$d->setEmptyString($this->fieldLabel('ManualEntry'));
			}

			if($this->Title)
				$fields->insertAfter(CheckboxField::create('UpdateTitle', $this->fieldLabel('UpdateTitle')), 'Title');

			$fields->insertAfter(LiteralField::create('SmugmugTips', '<p class="message">' . _t('Smugmuggable_Album.SmugmugTips',
				nl2br('	<strong>Note: Selecting an album will automatically generate the {id} and {key} fields.</strong>
						<strong>Tip: </strong> To add an unlisted Smugmug Album, please make the album public using the <a href="{url}" target="_blank">Smugmug Interface</a>, add the album by finding it in the listing here in the {application}, and then return to the <a href="{url}" target="_blank">Smugmug Interface</a> and make the album unlisted.'
				), array(
					'id' => $this->fieldLabel('SmugmugID'),
					'key' => $this->fieldLabel('SmugmugKey'),
					'url' => 'http://smugmug.com',
					'application' => singleton('LeftAndMain')->ApplicationName,
				)) . '</p>'), 'SmugmugAlbum');
		});

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
				$this->$var = SmugmugService_JSON::inst()->images($this->SmugmugID, $this->SmugmugKey, true, $size, $params);
			} catch(Exception $e) {
				if(Director::isDev())
					user_error($e->getMessage());

				$this->$var = null;
			}
		}

		return $this->$var;
	}

	public function getInfo() {
		if(!isset($this->_info) && $this->SmugmugID && $this->SmugmugKey) {
			try {
				$this->_info = SmugmugService_JSON::inst()->album($this->SmugmugID, $this->SmugmugKey);
			} catch(Exception $e) {
				if(Director::isDev())
					user_error($e->getMessage());

				$this->_info = null;
			}
		}

		return $this->_info;
	}

	private $_updating = false;

	public function saveSmugmugAlbum($album = null) {
		if($album && $album != $this->SmugmugID . '||' . $this->SmugmugKey) {
			list($id, $key) = explode('||', $album);

			$this->saveSmugmugID($id);
			$this->saveSmugmugKey($key);

			if($info = $this->Info)
				$this->saveTitle($info->Title);

			$this->_updating = true;
		}
	}

	public function saveSmugmugID($data = null) {
		if(!$this->_updating && $data)
			$this->SmugmugID = $data;
	}

	public function saveSmugmugKey($data = null) {
		if(!$this->_updating && $data)
			$this->SmugmugKey = $data;
	}

	public function saveTitle($data = null) {
		if(!$this->_updating && $data)
			$this->Title = $data;
	}

	public function saveUpdateTitle($do = null) {
		if(!$this->_updating && $do && $info = $this->Info)
			$this->Title = $info->Title;
	}
}