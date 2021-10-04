<?php
/**
 * @link      https://dukt.net/videos/
 *
 * @copyright Copyright (c) 2021, Dukt
 * @license   https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\events;

use yii\base\Event;

/**
 * RegisterGatewayTypesEvent class.
 *
 * @author Dukt <support@dukt.net>
 *
 * @since  2.0
 */
class RegisterGatewayTypesEvent extends Event
{
    /**
     * @var string name of the event
     */
    public const NAME = 'registerGatewayTypes';

    /**
     * @var array available gateway types
     */
    public array $gatewayTypes = [];
}
