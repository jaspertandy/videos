<?php
/**
 * @link https://dukt.net/videos/
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\models;

/**
 * Failed video embed model class.
 *
 * @author Dukt <support@dukt.net>
 * @since 3.0.0
 */
class FailedVideoEmbed extends AbstractVideoEmbed
{
    /**
     * @var array errors occurred during video embed generating
     *
     * @since 3.0.0
     */
    public array $errors = [];
}
