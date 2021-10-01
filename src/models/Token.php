<?php
/**
 * @link      https://dukt.net/videos/
 *
 * @copyright Copyright (c) 2021, Dukt
 * @license   https://github.com/dukt/videos/blob/master/LICENSE.md
 */

namespace dukt\videos\models;

use craft\base\Model;
use craft\helpers\Json;

class Token extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var null|int ID
     */
    public $id;

    /**
     * @var null|string Gateway
     */
    public $gateway;

    /**
     * @var null|string Access token
     */
    public $accessToken;

    /**
     * @var null|\DateTime Date updated
     */
    public $dateUpdated;

    /**
     * @var null|\DateTime Date created
     */
    public $dateCreated;

    /**
     * @var null|string Uid
     */
    public $uid;

    // Public Methods
    // =========================================================================

    /**
     * {@inheritdoc}
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
     */
    public function rules()
    {
        return [
            [['id'], 'number', 'integerOnly' => true],
        ];
    }
}
