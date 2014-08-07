<?php namespace Milkyway\SS\Smugmug\Api;
/**
 * Milkyway Multimedia
 * Utilities.php
 *
 * @package milkywaymultimedia.com.au
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

class Utilities {
    public static $environment = [];

    protected static $prefix = 'smugmug';

    protected static $environment_mapping = [
        'APIKey' => 'api_key',
        'Nickname' => 'nickname',
    ];

    public static function config() {
        return \Config::inst()->forClass('SmugmugConfig');
    }

    public static function env_value($setting, \ViewableData $object = null) {
        if($object) {
            if($object->hasMethod('SmugmugConfig') && $object->SmugmugConfig()->$setting)
                return $object->SmugmugConfig()->$setting;
            if($object->{ucfirst(static::$prefix) . '_' . $setting})
                return $object->{ucfirst(static::$prefix) . '_' . $setting};
        }

        if(isset(self::$environment[$setting]))
            return self::$environment[$setting];

        $value = null;

        if(isset(self::$environment_mapping[$setting])) {
            $dbSetting = $setting;
            $setting = self::$environment_mapping[$setting];

            if($object && $object->config()->$setting)
                $value = $object->config()->$setting;

            if (!$value)
                $value = static::config()->$setting;

            if (!$value && \ClassInfo::exists('SiteConfig')) {
                if (\SiteConfig::current_site_config()->SmugmugConfig()->$dbSetting) {
                    $value = \SiteConfig::current_site_config()->SmugmugConfig()->$dbSetting;
                } elseif (\SiteConfig::current_site_config()->{ucfirst(static::$prefix) . '_' . $dbSetting}) {
                    $value = \SiteConfig::current_site_config()->{ucfirst(static::$prefix) . '_' . $dbSetting};
                } elseif (\SiteConfig::config()->{static::$prefix . '_' . $setting}) {
                    $value = \SiteConfig::config()->{static::$prefix . '_' . $setting};
                }
            }

            if (!$value) {
                if (getenv(static::$prefix . '_' . $setting)) {
                    $value = getenv(static::$prefix . '_' . $setting);
                } elseif (isset($_ENV[static::$prefix . '_' . $setting])) {
                    $value = $_ENV[static::$prefix . '_' . $setting];
                }
            }

            if ($value) {
                self::$environment[$setting] = $value;
            }
        }

        return $value;
    }
} 