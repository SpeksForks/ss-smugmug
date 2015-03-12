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

	private static $db_to_environment_mapping = [
		'APIKey' => 'smugmug.api_key',
		'Nickname' => 'smugmug.nickname',
	];

    public function getCMSFields() {
        $this->addBeforeFieldMethodsCallback('updateCMSFields');
        return parent::getCMSFields();
    }

    public function getFrontEndFields($params = null) {
        $this->addBeforeFieldMethodsCallback('updateFrontEndFields');
        return parent::getFrontendFields($params);
    }

    protected function addBeforeFieldMethodsCallback($method) {
        $this->beforeExtending($method, function($fields) {
		        $callbacks = [];

                if($api = $fields->dataFieldByName('APIKey'))
                    $fields->replaceField('APIKey', $api->castedCopy('TextField')->setTitle(_t('Smugmug.API_KEY', 'API Key'))->setAttribute('placeholder', $this->setting('APIKey')));

                if($nickname = $fields->dataFieldByName('Nickname'))
                    $nickname->setAttribute('placeholder', $this->setting('Nickname'));
            }
        );
    }

	protected function setting($setting, $cache = true) {
		$callbacks = [];

		if(\ClassInfo::exists('SiteConfig')) {
			$callbacks['smugmug'] = function($keyParts, $key) use($setting) {
				$value = SiteConfig::current_site_config()->SmugmugConfig()->$setting;

				if(!$value)
					$value = SiteConfig::current_site_config()->{'Smugmug_' . $setting};

				if(!$value)
					$value = SiteConfig::current_site_config()->{str_replace('.', '_', $key)};

				return $value;
			};
		}

		return singleton('env')->get($setting, [$this->owner], null, null, $callbacks, $cache, $cache);
	}
} 