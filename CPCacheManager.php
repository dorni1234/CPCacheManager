<?php
/**
 * Plugin Name:     Coding Pioneers Cache Manager
 * Plugin URI:      https://github.com/dorni1234/CPCacheManager
 * Description:     Simple transient cache manager for Wordpress.
 * Author:          Sebastian Dornack <sebastian.d@coding-pioneers.com>
 * Author URI:      https://coding-pioneers.com
 * Text Domain:     cp-cache-manager
 * Domain Path:     /languages
 * Version:         0.2.0
 *
 * @package         CPCacheManager
 */

namespace CPCacheManager;

use CPCacheManager\Helpers\VersionHelper;

define('CPCACHE', get_option('cpcachemanager_enabled', false) ? true : false);

/**
 * Class CPCacheManager.
 *
 * @author  : Sebastian Dornack <sebastian.d@coding-pioneers.com>
 * @company : Coding Pioneers GmbH (https://coding-pioneers.com)
 * @created : 04.08.20
 * @updated : 04.08.20
 * @version : 0.2.0
 * @package CPCacheManager
 */
class CPCacheManager
{
    protected const TRANSIENT_PREFIX = 'coding_pioneers_transient_';

    protected string $cacheVersion = '1.0.0';

    /**
     * CPCacheManager constructor.
     */
    public function __construct()
    {
        if ($cacheVersion = get_option('cpcachemanager_cache_version')) {
            if (VersionHelper::assertVersionStringHasCorrectFormat($cacheVersion)) {
                $this->cacheVersion = $cacheVersion;
            }
        }
    }

    /**
     * Set the default version the cache manager uses for every cache entry when no version is explicitly given
     * for save, get or delete.
     *
     * @param string $newCacheVersion
     * @param bool   $cleanup
     * @param bool   $force
     *
     * @return bool
     */
    public function setGlobalCacheVersion(string $newCacheVersion, bool $cleanup = true, bool $force = false)
    {
        if (!VersionHelper::assertVersionStringHasCorrectFormat($newCacheVersion)) {
            // TODO give error message for invalid
            return false;
        }
        if ($newCacheVersion <= $this->cacheVersion && !$force) {
            return false;
            // TODO give error message that cache version is less or equal to old version.
        }

        $this->cacheVersion = $newCacheVersion;
        update_option('cpcachemanager_cache_version', $this->cacheVersion);
        if ($cleanup) {
            $this->deleteOldCacheVersions(true);
        }
        return true;
    }

    /**
     * Returns the transient cache name to use in the get_transient, set_transient, delete_transient functions.
     *
     * @param string      $transientName
     * @param string|null $cacheVersion
     *
     * @return string
     */
    public function getTransientCacheName(string $transientName, string $cacheVersion = null)
    {
        return self::TRANSIENT_PREFIX . $transientName . '_' . ($cacheVersion ?: $this->cacheVersion);
    }

    /**
     * Retrieve data from the transient cache.
     *
     * @param string      $transientName
     * @param string|null $cacheVersion
     *
     * @return mixed
     */
    public function getFromCache(string $transientName, string $cacheVersion = null)
    {
        return CPCACHE ? get_transient($this->getTransientCacheName($transientName, $cacheVersion)) : false;
    }

    /**
     * Save data to the transient cache.
     *
     * @param string      $transientName
     * @param             $data
     * @param string|null $version
     * @param int         $cacheLifetimeInSeconds
     *
     * @return bool
     */
    public function saveToCache(string $transientName, $data, string $version = null, int $cacheLifetimeInSeconds = 0)
    {
        $cacheVersions = get_option('coding_pioneers_transient_index', false);
        if (!$cacheVersions) {
            update_option('coding_pioneers_transient_index', maybe_serialize([$transientName => ($version ?: $this->cacheVersion)]));
            set_transient($this->getTransientCacheName($transientName, $version), $data, $cacheLifetimeInSeconds);
            return true;
        }

        $unserializedCacheVersions = maybe_unserialize($cacheVersions);
        if (!is_array($unserializedCacheVersions)) {
            update_option('coding_pioneers_transient_index', maybe_serialize([$transientName => ($version ?: $this->cacheVersion)]));
        } elseif (!isset($unserializedCacheVersions[$transientName]) || $unserializedCacheVersions[$transientName] < ($version ?: $this->cacheVersion)) {
            $unserializedCacheVersions[$transientName] = ($version ?: $this->cacheVersion);
            update_option('coding_pioneers_transient_index', maybe_serialize($unserializedCacheVersions));
        } elseif ($unserializedCacheVersions[$transientName] > ($version ?: $this->cacheVersion)) {
            return false;
            // TODO FAIL
        }

        set_transient($this->getTransientCacheName($transientName, $version), $data, $cacheLifetimeInSeconds);
        return true;
    }

    /**
     * Delete data from the transient cache.
     *
     * @param string      $transientName
     * @param string|null $version
     *
     * @return bool
     */
    public function deleteCache(string $transientName, string $version = null)
    {
        return delete_transient($this->getTransientCacheName($transientName, $version));
    }

    /**
     * Delete old versions of every cache name saved in the  coding_pioneers_transient_index option.
     *
     * @param bool $useGlobalCacheVersion
     */
    public function deleteOldCacheVersions(bool $useGlobalCacheVersion = false)
    {
        global $wpdb;
        $caches = $this->getAllTransitionalCaches();
        foreach ($caches as $cacheName => $cacheVersion) {
            $cacheVersionToUse = $useGlobalCacheVersion ? $this->cacheVersion : $cacheVersion;
            $queryString = $wpdb->prepare("SELECT REGEXP_SUBSTR(option_name, '[0-9]+\.[0-9]+\.[0-9]+$') AS 'version' FROM wp_options WHERE option_name > %s AND option_name < %s;", "_transient_coding_pioneers_transient_{$cacheName}_0.0.0", "_transient_coding_pioneers_transient_{$cacheName}_{$cacheVersionToUse}");
            $staleVersions = $wpdb->get_results($queryString);
            foreach ($staleVersions as $staleVersion) {
                delete_transient($this->getTransientCacheName($cacheName, $staleVersion->version));
            }
        }
    }

    /**
     * Get coding_pioneers_transient_index.
     * @return array|mixed|string
     */
    protected function getAllTransitionalCaches()
    {
        $cacheVersions = get_option('coding_pioneers_transient_index', false);
        if (!$cacheVersions) {
            return [];
        }
        return maybe_unserialize($cacheVersions);
    }

    /**
     * Activation hook.
     */
    public static function activationHook()
    {
        update_option('cpcachemanager_enabled', true);
    }

    /**
     * Deactivation hook.
     */
    public static function deactivationHook()
    {
        update_option('cpcachemanager_enabled', false);
    }

    /**
     * Uninstall hook.
     */
    public static function uninstallHook()
    {
        delete_option('cpcachemanager_enabled');
        delete_option('cpcachemanager_cache_version');
    }
}

register_activation_hook(__FILE__, [CPCacheManager::class, 'activationHook']);
register_deactivation_hook(__FILE__, [CPCacheManager::class, 'deactivationHook']);
register_uninstall_hook(__FILE__, [CPCacheManager::class, 'uninstallHook']);
