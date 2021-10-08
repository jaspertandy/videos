<?php
/**
 * @link https://dukt.net/videos/
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\models;

/**
 * Failed video model class.
 *
 * @author Dukt <support@dukt.net>
 * @since 3.0.0
 */
class FailedVideo extends AbstractVideo
{
    /**
     * @var array errors occurred during video retrieving
     *
     * @since 3.0.0
     */
    public array $errors = [];
}
