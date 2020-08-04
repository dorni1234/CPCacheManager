# CPCacheManager
## Description
CPCacheManager is a simple transient cache management plugin for Wordpress.

It allows for easy saving and retrieving data from the transient cache from Wordpress. By appending a version number to
each cache entry, simple cache invalidation is possible.

## Requirements
- PHP 7.4 (usage of typed properties etc.)
- Wordpress (tested in version 5.4.2)
## Installation
Copy this plugin to your plugins/mu-plugins folder and enable it in the Wordpress admin panel.

## Usage
```php
# By default, the cache manager initializes with cache version 1.0.0
$cacheManager = new \CPCacheManager\CPCacheManager();

/* ---------------- */

# Save data to the cache, use default cache version
$cacheManager->saveToCache('cachename', ['this is cached data']);

# Retrieve data from the cache, use default cache version
$cachedData = $cacheManager->getFromCache('cachename');

/* ---------------- */

# Save a new cache version, so the old cached data won't be used anymore
# Notice the version format: x.y.z
$cacheManager->saveToCache('cachename', ['this is newer cached data'], '1.2.1');

# Retrieve a specific cache version, if the global cache version is not used
$cachedData = $cacheManager->getFromCache('cachename', '1.2.1');

/* ---------------- */

# Bump the global cache version, this version number is saved for later use. You only need to set this once.
# Deletes all previous caches, sets the default cache version
$cacheManager->setGlobalCacheVersion('1.3.0');

# Delete older cache versions, only keep the newest one
$cacheManager->deleteOldCacheVersions();
```
