<?php

namespace CPCacheManager\Helpers;

/**
 * Class VersionHelper Helper class to ensure the use of correct version strings.
 *
 * @author  : Sebastian Dornack <sebastian.d@coding-pioneers.com>
 * @company : Coding Pioneers GmbH (https://coding-pioneers.com)
 * @created : 04.08.20
 * @updated : 04.08.20
 * @version : 1.0
 * @package CPCacheManager\Helpers
 */
class VersionHelper
{
    protected const VERSION_STRING_FORMAT_REGEX = '/\d+\.\d+\.\d+/';

    /**
     * Asserts that the version string given is in the correct format.
     *
     * @param string $versionString
     *
     * @return bool
     */
    public static function assertVersionStringHasCorrectFormat(string $versionString)
    {
        return 1 === preg_match(self::VERSION_STRING_FORMAT_REGEX, $versionString);
    }
}
