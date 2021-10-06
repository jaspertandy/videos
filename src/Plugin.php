<?php
/**
 * @link https://dukt.net/videos/
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos;

use Craft;
use craft\base\Plugin as BasePlugin;
use craft\events\RegisterCacheOptionsEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\UrlHelper;
use craft\services\Fields;
use craft\utilities\ClearCaches;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use dukt\videos\base\PluginTrait;
use dukt\videos\fields\Video as VideoField;
use dukt\videos\models\Settings;
use dukt\videos\services\Cache;
use dukt\videos\services\Gateways;
use dukt\videos\services\Oauth;
use dukt\videos\services\Tokens;
use dukt\videos\services\Videos;
use dukt\videos\web\twig\variables\VideosVariable;
use Exception;
use yii\base\Event;

/**
 * Videos plugin class.
 *
 * @author Dukt <support@dukt.net>
 * @since 2.0.0
 */
class Plugin extends BasePlugin
{
    use PluginTrait;

    /**
     * @var string prefix for cache key
     *
     * @since 3.0.0
     */
    public const CACHE_KEY_PREFIX = 'videos';

    /**
     * {@inheritdoc}
     *
     * @since 2.0.12
     */
    public $schemaVersion = '1.0.3';

    /**
     * {@inheritdoc}
     *
     * @since 2.0.0
     */
    public $hasCpSettings = true;

    /**
     * @var Plugin the plugin instance
     *
     * @since 2.0.0
     */
    public static $plugin;

    /**
     * {@inheritdoc}
     *
     * @since 2.0.0
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        $this->_setPluginComponents();
        $this->_registerCpRoutes();
        $this->_registerFieldTypes();
        $this->_registerCacheOptions();
        $this->_registerVariable();
    }

    /**
     * {@inheritdoc}
     *
     * @since 2.0.0
     */
    public function getSettingsResponse()
    {
        $url = UrlHelper::cpUrl('videos/settings');

        return Craft::$app->controller->redirect($url);
    }

    /**
     * Get OAuth provider options.
     *
     * @param string $gatewayHandle
     * @param bool $parse
     * @return null|array
     *
     * @since 2.0.2
     * @deprecated in 3.0.0, will be removed in 3.1.0, use [[Oauth::getOauthProviderOptions]] instead.
     */
    public function getOauthProviderOptions(string $gatewayHandle, bool $parse = true)
    {
        try {
            $gateway = $this->getGateways()->getGatewayByHandle($gatewayHandle);
            $options = $this->getOauth()->getOauthProviderOptions($gateway, $parse);

            if (empty($options) === false) {
                return $options;
            }
        } catch (Exception $e) {
        }

        return null;
    }

    /**
     * {@inheritdoc}
     *
     * @since 2.0.0
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * Set plugin components.
     *
     * @return void
     *
     * @since 2.0.3
     */
    private function _setPluginComponents(): void
    {
        $this->setComponents([
            'videos' => Videos::class,
            'cache' => Cache::class,
            'gateways' => Gateways::class,
            'oauth' => Oauth::class,
            'tokens' => Tokens::class,
        ]);
    }

    /**
     * Register CP routes.
     *
     * @return void
     *
     * @since 2.0.3
     */
    private function _registerCpRoutes(): void
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function (RegisterUrlRulesEvent $event) {
            $rules = [
                'videos/settings' => 'videos/settings/index',
                'videos/settings/<gatewayHandle:{handle}>' => 'videos/settings/gateway',
                'videos/settings/<gatewayHandle:{handle}>/oauth' => 'videos/settings/gateway-oauth',
            ];

            $event->rules = array_merge($event->rules, $rules);
        });
    }

    /**
     * Register field types.
     *
     * @return void
     *
     * @since 2.0.3
     */
    private function _registerFieldTypes(): void
    {
        Event::on(Fields::class, Fields::EVENT_REGISTER_FIELD_TYPES, function (RegisterComponentTypesEvent $event) {
            $event->types[] = VideoField::class;
        });
    }

    /**
     * Register cache options.
     *
     * @return void
     *
     * @since 2.0.3
     */
    private function _registerCacheOptions(): void
    {
        Event::on(ClearCaches::class, ClearCaches::EVENT_REGISTER_CACHE_OPTIONS, function (RegisterCacheOptionsEvent $event) {
            $event->options[] = [
                'key' => 'videos-caches',
                'label' => Craft::t('videos', 'Videos caches'),
                'action' => Craft::$app->path->getRuntimePath().'/videos',
            ];
        });
    }

    /**
     * Register template variable.
     *
     * @return void
     *
     * @since 2.0.3
     */
    private function _registerVariable(): void
    {
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function (Event $event) {
            /** @var CraftVariable $variable */
            $variable = $event->sender;
            $variable->set('videos', VideosVariable::class);
        });
    }
}
