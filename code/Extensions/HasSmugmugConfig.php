<?php
/**
 * Milkyway Multimedia
 * SiteConfig.php
 *
 * @package milkywaymultimedia.com.au
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

namespace Milkyway\SS\Smugmug\Extensions;

class HasSmugmugConfig extends \DataExtension
{
    private static $has_one = array(
        'SmugmugConfig' => 'SmugmugConfig',
    );

    protected $tab = 'Smugmug';
    protected $useCMSFieldsAlways;

    public function __construct($tab = 'Smugmug', $useCMSFieldsAlways = false)
    {
        parent::__construct();
        $this->tab = $tab;
        $this->useCMSFieldsAlways = $useCMSFieldsAlways;
    }

    function updateCMSFields(\FieldList $fields)
    {
        if (!$this->useCMSFieldsAlways && ($this->owner instanceof \SiteTree)) {
            return;
        }

        $this->updateFields($fields);
    }

    function updateSettingsFields($fields)
    {
        if (!$this->useCMSFieldsAlways && ($this->owner instanceof \SiteTree)) {
            $this->updateFields($fields);
        }
    }

    protected function updateFields($fields) {
        $fields->addFieldsToTab(
            'Root.' . $this->tab,
            [
                \HasOneCompositeField::create('SmugmugConfig')
            ]
        );
    }
}