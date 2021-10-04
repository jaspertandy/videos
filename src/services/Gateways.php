<?php
/**
 * @link      https://dukt.net/videos/
 *
 * @copyright Copyright (c) 2021, Dukt
 * @license   https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\services;

use dukt\videos\base\Gateway;
use dukt\videos\errors\GatewayNotFoundException;
use dukt\videos\events\RegisterGatewayTypesEvent;
use dukt\videos\gateways\Vimeo;
use dukt\videos\gateways\YouTube;
use yii\base\Component;

/**
 * Gateways service.
 *
 * An instance of the Gateways service is globally accessible via [[Plugin::gateways `Videos::$plugin->getGateways()`]].
 *
 * @author Dukt <support@dukt.net>
 *
 * @since  2.0
 */
class Gateways extends Component
{
    /**
     * @var array all gateways
     */
    private array $_gateways = [];

    /**
     * {@inheritdoc}
     *
     * @throws InvalidConfigException
     * @throws GatewayNotFoundException
     */
    public function init(): void
    {
        parent::init();

        foreach ($this->_getGatewayTypes() as $gatewayType) {
            // type needs to be a valid path to an existing class
            if (class_exists($gatewayType) === false) {
                throw new GatewayNotFoundException(/* TODO: more precise message */);
            }

            $gateway = new $gatewayType();

            $this->_gateways[] = $gateway;
        }
    }

    /**
     * Get all gateways.
     *
     * @param null|bool $loggedInStatus
     *
     * @return Gateway[]
     *
     * @throws InvalidConfigException
     * @throws GatewayNotFoundException
     */
    public function getGateways(?bool $loggedInStatus = null): array
    {
        if ($loggedInStatus !== null) {
            return array_filter($this->_gateways, function ($_gateway) use ($loggedInStatus) {
                return $_gateway->isOauthLoggedIn() === $loggedInStatus;
            });
        }

        return $this->_gateways;
    }

    /**
     * Has gateway logged in.
     *
     * @return bool
     *
     * @throws InvalidConfigException
     * @throws GatewayNotFoundException
     */
    public function hasGatewaysLoggedIn(): bool
    {
        return count($this->getGateways(true)) > 0;
    }

    /**
     * Get one gateway by handle.
     *
     * @param string    $gatewayHandle
     * @param null|bool $loggedInStatus
     *
     * @return Gateway
     *
     * @throws InvalidConfigException
     * @throws GatewayNotFoundException
     */
    public function getGatewayByHandle(string $gatewayHandle, ?bool $loggedInStatus = null): Gateway
    {
        foreach ($this->getGateways($loggedInStatus) as $gateway) {
            if ($gateway->getHandle() === $gatewayHandle) {
                return $gateway;
            }
        }

        throw new GatewayNotFoundException(/* TODO: more precise message */);
    }

    /**
     * Returns gateway types.
     *
     * @return array
     */
    private function _getGatewayTypes(): array
    {
        $gatewayTypes = [
            Vimeo::class,
            YouTube::class,
        ];

        $event = new RegisterGatewayTypesEvent([
            'gatewayTypes' => $gatewayTypes,
        ]);

        $this->trigger(RegisterGatewayTypesEvent::NAME, $event);

        sort($event->gatewayTypes);

        return $event->gatewayTypes;
    }
}
