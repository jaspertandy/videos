<?php
/**
 * @link https://dukt.net/videos/
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use dukt\videos\models\FailedVideo;
use dukt\videos\Plugin as VideosPlugin;
use dukt\videos\web\assets\videofield\VideoFieldAsset;

/**
 * Video field.
 *
 * @author Dukt <support@dukt.net>
 * @since 2.0.0
 */
class Video extends Field
{
    /**
     * Returns the field’s name.
     *
     * @return string
     *
     * @since 2.0.0
     */
    public function getName(): string
    {
        return Craft::t('videos', 'Videos');
    }

    /**
     * {@inheritdoc}
     *
     * @since 2.0.0
     *
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function getInputHtml($value, ?ElementInterface $element = null): string
    {
        $view = Craft::$app->getView();
        $name = $this->handle;

        // Reformat the input name into something that looks more like an ID
        $id = $view->formatInputId($name);

        // Init CSRF Token
        $jsTemplate = 'window.csrfTokenName = "'.Craft::$app->getConfig()->getGeneral()->csrfTokenName.'";';
        $jsTemplate .= 'window.csrfTokenValue = "'.Craft::$app->getRequest()->getCsrfToken().'";';
        $js = $view->renderString($jsTemplate);
        $view->registerJs($js);

        // Asset bundle
        $view->registerAssetBundle(VideoFieldAsset::class);

        // Preview
        $preview = $view->renderTemplate('videos/_elements/fieldPreview', ['video' => $value]);

        if (VideosPlugin::$plugin->getGateways()->hasGatewaysLoggedIn()) {
            // Instantiate Videos Field
            $view->registerJs('new Videos.Field("'.$view->namespaceInputId($id).'");');
        }

        return $view->renderTemplate('videos/_components/fieldtypes/Video/input', [
            'id' => $id,
            'name' => $name,
            'value' => $value,
            'preview' => $preview,
            'hasGatewaysLoggedIn' => VideosPlugin::$plugin->getGateways()->hasGatewaysLoggedIn(),
        ]);
    }

    /**
     * {@inheritdoc}
     *
     * @since 2.0.0
     */
    public function serializeValue($value, ElementInterface $element = null)
    {
        if (!empty($value->url)) {
            return Db::prepareValueForDb($value->url);
        }

        return parent::serializeValue($value, $element);
    }

    /**
     * {@inheritdoc}
     *
     * @since 2.0.0
     */
    public function normalizeValue($value, ElementInterface $element = null)
    {
        if (empty($value)) {
            return null;
        }

        if ($value instanceof \dukt\videos\models\AbstractVideo) {
            return $value;
        }

        try {
            return VideosPlugin::$plugin->getVideos()->getVideoByUrl($value);
        } catch (\Exception $e) {
            $errorMessage = "Couldn't get video in field normalizeValue: ".$e->getMessage();

            Craft::info($errorMessage, __METHOD__);

            return new FailedVideo([
                'url' => $value,
                'errors' => [
                    $errorMessage,
                ],
            ]);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     *
     * @since 2.0.0
     */
    public function getSearchKeywords($value, ElementInterface $element): string
    {
        $keywords = [];

        if ($value instanceof \dukt\videos\models\Video) {
            $keywords[] = $value->id;
            $keywords[] = $value->url;
            $keywords[] = $value->title;
            $keywords[] = $value->description;
            $keywords[] = $value->author->name;
            $keywords[] = $value->gateway->handle;
            $keywords[] = $value->gateway->name;
        }

        $searchKeywords = StringHelper::toString($keywords, ' ');

        return StringHelper::encodeMb4($searchKeywords);
    }
}
