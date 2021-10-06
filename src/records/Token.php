<?php
/**
 * @link https://dukt.net/videos/
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\records;

use craft\db\ActiveRecord;

/**
 * Token record class.
 *
 * @author Dukt <support@dukt.net>
 * @since 2.0.8
 */
class Token extends ActiveRecord
{
    /**
     * Returns the name of the associated database table.
     *
     * @return string
     *
     * @since 2.0.8
     */
    public static function tableName(): string
    {
        return '{{%videos_tokens}}';
    }
}
