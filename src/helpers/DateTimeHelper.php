<?php
/**
 * @link https://dukt.net/videos/
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\helpers;

use DateInterval;

/**
 * DateTime helper.
 *
 * @author Dukt <support@dukt.net>
 * @since 3.0.0
 */
class DateTimeHelper
{
    /**
     * Formats a date interval to readable.
     *
     * @param DateInterval $interval
     * @return string
     *
     * @since 3.0.0
     */
    public static function formatDateIntervalToReadable(DateInterval $interval): string
    {
        if ($interval->h > 0) {
            return $interval->format('%H:%I:%S');
        }

        return $interval->format('%I:%S');
    }

    /**
     * Formats a date interval to ISO 8601.
     *
     * @param DateInterval $interval
     * @return string
     *
     * @since 3.0.0
     */
    public static function formatDateIntervalToISO8601(DateInterval $interval): string
    {
        static $f = ['S0F', 'M0S', 'H0M', 'DT0H', 'M0D', 'P0Y', 'Y0M', 'P0M'];
        static $r = ['S', 'M', 'H', 'DT', 'M', 'P', 'Y', 'P'];

        return rtrim(str_replace($f, $r, $interval->format('P%yY%mM%dDT%hH%iM%sS%fF')), 'PT') ?: 'PT0F';
    }
}
