<?php
/**
 * @link https://dukt.net/videos/
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\models;

use craft\base\Model;
use craft\helpers\Json;

/**
 * Settings model class.
 *
 * @author Dukt <support@dukt.net>
 * @since 2.0.8
 */
class Token extends Model
{
    /**
     * @var null|int the token's ID
     *
     * @since 2.0.8
     */
    public $id;

    /**
     * @var null|string the token's gateway
     *
     * @since 2.0.8
     */
    public $gateway;

    /**
     * @var null|string the token's access token
     *
     * @since 2.0.8
     */
    public $accessToken;

    /**
     * @var null|\DateTime the token's date updated
     *
     * @since 2.0.8
     */
    public $dateUpdated;

    /**
     * @var null|\DateTime the token's date created
     *
     * @since 2.0.8
     */
    public $dateCreated;

    /**
     * @var null|string the token's uid
     *
     * @since 2.0.8
     */
    public $uid;

    /**
     * {@inheritdoc}
     *
     * @since 2.0.8
     */
    public function init()
    {
        parent::init();

        if (is_string($this->accessToken)) {
            $this->accessToken = Json::decode($this->accessToken);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @since 2.0.8
     */
    public function rules()
    {
        return [
            [['id'], 'number', 'integerOnly' => true],
        ];
    }
}
