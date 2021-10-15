<?php
/**
 * @link https://dukt.net/videos/
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\models;

use craft\base\Model;

/**
 * Video explorer model class.
 *
 * @author Dukt <support@dukt.net>
 * @since 3.0.0
 */
class VideoExplorer extends Model
{
    /**
     * @var VideoExplorerSection[] the section's collections
     *
     * @since 3.0.0
     */
    public array $sections = [];
}
