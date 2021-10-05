<?php
/**
 * @link      https://dukt.net/videos/
 *
 * @copyright Copyright (c) 2021, Dukt
 * @license   https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\helpers;

/**
 * Url helper.
 */
class UrlHelper
{
    /**
     * Build url.
     *
     * @param array $parts
     *
     * @return string
     */
    public static function buildUrl(array $parts): string
    {
        $scheme = isset($parts['scheme']) ? ($parts['scheme'].'://') : '';

        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? (':'.$parts['port']) : '';

        $user = $parts['user'] ?? '';
        $pass = isset($parts['pass']) ? (':'.$parts['pass']) : '';
        $pass = ($user || $pass) ? ($pass.'@') : '';

        $path = $parts['path'] ?? '';

        $query = empty($parts['query']) ? '' : ('?'.$parts['query']);

        $fragment = empty($parts['fragment']) ? '' : ('#'.$parts['fragment']);

        return implode('', [$scheme, $user, $pass, $host, $port, $path, $query, $fragment]);
    }
}
