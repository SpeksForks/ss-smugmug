<?php
/**
 * Milkyway Multimedia
 * SiteConfig.php
 *
 * @package milkywaymultimedia.com.au
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

namespace Milkyway\SS\Smugmug\Extensions;


class SiteConfig extends \DataExtension {
    private static $has_one = array(
        'SmugmugConfig' => 'SmugmugConfig',
    );

    protected $tab = 'Smugmug';

    public function __construct($tab = 'Smugmug') {
        parent::__construct();
        $this->tab = $tab;
    }

    function updateCMSFields(\FieldList $fields)
    {
        $fields->addFieldToTab(
            'Root.' . $this->tab,
            \HasOneCompositeField::create('SmugmugConfig')
        );
    }
} 