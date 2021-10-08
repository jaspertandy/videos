<?php
/**
 * @link https://dukt.net/videos/
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\models;

use craft\base\Model;

/**
 * Video explorer section model class.
 *
 * @author Dukt <support@dukt.net>
 * @since 3.0.0
 */
class VideoExplorerSection extends Model
{
    /**
     * @var string the section's name
     *
     * @since 3.0.0
     */
    public string $name;

    /**
     * @var VideoExplorerCollection[] the section's collections
     *
     * @since 3.0.0
     */
    public array $collections = [];
}
