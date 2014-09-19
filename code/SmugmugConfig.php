<?php
use Milkyway\SS\Smugmug\Api\Utilities;

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
                if($api = $fields->dataFieldByName('APIKey'))
                    $fields->replaceField('APIKey', $api->castedCopy('TextField')->setTitle(_t('Smugmug.API_KEY', 'API Key'))->setAttribute('placeholder', Utilities::env_value('APIKey', $this->owner)));

                if($nickname = $fields->dataFieldByName('Nickname'))
                    $nickname->setAttribute('placeholder', Utilities::env_value('Nickname', $this->owner));
            }
        );
    }
} 