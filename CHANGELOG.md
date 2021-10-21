Changelog
=========

## Unreleased

### Added
- Added Vimeo folders support.
- Added collection icon support.
- Added `dukt\videos\Plugin::CACHE_KEY_PREFIX` used for cache key prefix with new cache system
- Added `dukt\videos\base\Cacheable` interface to mark an object as cacheable
- Added `dukt\videos\base\Exception` base class for plugin exceptions
- Added `dukt\videos\base\Gateway::getOauthLoginUrl()` returns the oauth login URL
- Added `dukt\videos\base\Gateway::getOauthRedirectUrl()` returns the oauth redirect URL
- Added `dukt\videos\base\Gateway::getOauthLogoutUrl()` returns the oauth logout URL
- Added `dukt\videos\base\Gateway::getOauthJavascriptOrigin()` returns the oauth javascript origin URL
- Added `dukt\videos\base\Gateway::getOauthAuthorizationUrl()` returns the oauth authorization URL
- Added `dukt\videos\base\Gateway::getOauthAccessToken()` returns the oauth access token
- Added `dukt\videos\base\Gateway::oauthLogin()` login to oauth provider and save token in database
- Added `dukt\videos\base\Gateway::isEnabled()` check if gateway is enable (= logged in to oauth provider)
- Added `dukt\videos\base\Gateway::oauthLogout()` remove token from database and so logout to oauth provider
- Added `dukt\videos\base\Gateway::getOauthAccount()` returns oauth account information (in a `dukt\videos\models\OAuthAccount`instance)
- Added `dukt\videos\base\Gateway::extractVideoIdFromVideoUrl()` to extracts the video ID from the video URL
- Added `dukt\videos\base\Gateway::createApiClient()` to creates an authenticated guzzle client
- Added `dukt\videos\base\Gateway::getApiClient()` returns api client
- Added `dukt\videos\base\Gateway::fetchVideoById()` to get video data from the gateway API
- Added `dukt\videos\base\Gateway::jsonSerialize()` to serialize gateway information (used by javascript layer)
- Added `dukt\videos\base\Gateway::getExplorer()` returns the videos'explorer
- Added `dukt\videos\base\Gateway::fetch()` to get data from the gateway API
- Added `dukt\videos\controllers\OauthController::actionLogin()` to login to gateway
- Added `dukt\videos\controllers\OauthController::actionLogout()` to logout from gateway
- Added `dukt\videos\controllers\ExplorerController::actionGetGateways()` returns all enabled gateways
- Added `dukt\videos\controllers\ExplorerController::actionGetVideo()` returns a video by url
- Added `dukt\videos\controllers\ThumbnailController` to show thumbnail
- Added `dukt\videos\errors\ApiClientCreateException`
- Added `dukt\videos\errors\OauthAccessTokenNotFoundException`
- Added `dukt\videos\errors\OauthAccountNotFoundException`
- Added `dukt\videos\errors\OauthDeleteAccessTokenException`
- Added `dukt\videos\errors\OauthLoginException`
- Added `dukt\videos\errors\OauthLogoutException`
- Added `dukt\videos\errors\OauthRefreshAccessTokenException`
- Added `dukt\videos\errors\OauthSaveAccessTokenException`
- Added `dukt\videos\errors\TokenDeleteException`
- Added `dukt\videos\errors\TokenInvalidException`
- Added `dukt\videos\errors\TokenNotFoundException`
- Added `dukt\videos\errors\TokenSaveException`
- Added `dukt\videos\errors\VideoIdExtractException`
- Added `dukt\videos\events\RegisterGatewayTypesEvent::NAME`
- Added `dukt\videos\helpers\DateTimeHelper`
- Added `dukt\videos\helpers\EmbedHelper`
- Added `dukt\videos\helpers\ThumbnailHelper`
- Added `dukt\videos\helpers\UrlHelper`
- Added `dukt\videos\models\AbstractVideo` as parent video model class
- Added `dukt\videos\models\FailedVideo` returns when Video can’t be load for technical reason (api call failed, gateway disconnected and so on)
- Added `dukt\videos\models\OauthAccount` used for keep Gateway account information
- Added `dukt\videos\models\VideoAuthor` used for Video object author property
- Added `dukt\videos\models\VideoExplorer` the new explorer object
- Added `dukt\videos\models\VideoExplorerCollection` used by new explorer
- Added `dukt\videos\models\VideoExplorerSection` used by new explorer
- Added `dukt\videos\models\VideoSize` used for Video object size property
- Added `dukt\videos\models\VideoStatistic` used for Video object statistic property
- Added `dukt\videos\models\VideoThumbnail` used for Video object thumbnail property
- Added `dukt\videos\models\Video::CACHE_KEY_PREFIX` used for cache key prefix with new cache system
- Added `dukt\videos\models\Video::generateCacheKey()` used for generate cache key with new cache system
- Added `dukt\videos\models\Video::jsonSerialize()` used for json encode
- Added `dukt\videos\services\Cache::isEnabled()` to check if cache for plugin data is enabled
- Added `dukt\videos\services\Cache::duration()` returns cache duration for plugin data
- Added `dukt\videos\services\Gateways::hasEnabledGateways()` to check if at least one gateway has been enabled (= logged in with oauth)
- Added `dukt\videos\web\twig\Extension` with two twig filter : durationNumeric and durationIso8601 that use `dukt\videos\helpers\DateTimeHelper` to work with `\DateInterval` in twig

### Changed
- Renamed Vimeo’s “Playlists” section to “Showcases”.
- Renamed Vimeo’s “Favorites” to “Likes”.
- Use Vue.js for JavaScript interactions.
- Updated NPM dependencies.
- Changed `dukt\videos\base\Gateway::getHandle()` is now final
- Changed `dukt\videos\base\Gateway::getIconUrl()` is now final
- Changed `dukt\videos\base\Gateway::getOauthProvider()` is now final
- Moved `dukt\videos\base\Gateway::getRedirectUri()` to `dukt\videos\base\Gateway::getOauthRedirectUrl()`
- Moved `dukt\videos\base\Gateway::getJavascriptOrigin()` to `dukt\videos\base\Gateway::getOauthJavascriptOrigin()`
- Changed `dukt\videos\base\Gateway::getOauthScope()` always returns an array
- Changed `dukt\videos\base\Gateway::getOauthAuthorizationOptions()` always returns an array
- Moved `dukt\videos\base\Gateway::getOauthToken()` to `dukt\videos\base\Gateway::getOauthAccessToken()`
- Moved `dukt\videos\base\Gateway::hasToken()` to `dukt\videos\base\Gateway::isEnabled()`
- Moved `dukt\videos\base\Gateway::getAccount()` to `dukt\videos\base\Gateway::getOauthAccount()`
- Changed `dukt\videos\base\Gateway::getVideoByUrl()` is now final
- Changed `dukt\videos\base\Gateway::getVideoById()` is now final ; use `dukt\videos\base\Gateway::fetchVideoById()` to get video from the gateway API
- Moved `dukt\videos\base\Gateway::getEmbedFormat()` to `dukt\videos\base\Gateway::getEmbedUrlFormat()`
- Moved `dukt\videos\base\Gateway::getEmbed()` to `dukt\videos\helpers\EmbedHelper::getEmbedHtml()` and need `dukt\videos\model\Video` as param (instead of video id) and there is a split of html/url options in params
- Moved `dukt\videos\base\Gateway::getEmbedUrl()`  to `dukt\videos\helpers\EmbedHelper::getEmbedUrl()` and need `dukt\videos\model\Video` as param (instead of video id)
- Changed `dukt\videos\base\Gateway::getVideos()` is now final
- Moved `dukt\videos\base\Gateway::getExplorerSections()` to `dukt\videos\base\Gateway::getExplorer()`
- Moved `dukt\videos\base\Gateway::extractVideoIdFromUrl()` to `dukt\videos\base\Gateway::extractVideoIdFromVideoUrl()`
- Removed `dukt\videos\base\Gateway::oauthConnect()`
- Removed `dukt\videos\base\Gateway::oauthCallback()`
- Moved `dukt\videos\base\Gateway::createClient()` to `dukt\videos\base\Gateway::createApiClient()`
- Moved `dukt\videos\base\Gateway::get()` to `dukt\videos\base\Gateway::fetch()`
- Removed `dukt\videos\base\GatewayInterface`
- Moved `dukt\videos\controllers\OauthController::actionConnect()` to `dukt\videos\controllers\OauthController::actionLogin()`
- Moved `dukt\videos\controllers\OauthController::actionDisonnect()` to `dukt\videos\controllers\OauthController::actionLogout()`
- Removed `dukt\videos\controllers\ExplorerController::actionGetModal()`
- Removed `dukt\videos\controllers\ExplorerController::actionFieldPreview()`
- Removed `dukt\videos\controllers\ExplorerController::actionPlayer()`
- Removed `dukt\videos\errors\CollectionParsingException`
- Removed `dukt\videos\errors\JsonParsingException`
- Moved `dukt\videos\models\Collection` to `dukt\videos\models\VideoExplorerCollection`
- Moved `dukt\videos\models\Section` to `dukt\videos\models\VideoExplorerSection`
- Changed `dukt\videos\models\Settings::$cacheDuration` is now integer of seconds before the cache will expire
- Moved `dukt\videos\models\Video::$date` to `dukt\videos\models\Video::$publishedAt`
- Moved `dukt\videos\models\Video::$plays` to `dukt\videos\models\Video::$statistic::$playCount` $statistic is a `dukt\videos\models\VideoStatistic` instance
- Moved `dukt\videos\models\Video::$width` to `dukt\videos\models\Video::$size::$width` $size is a `dukt\videos\models\VideoSize` instance
- Moved `dukt\videos\models\Video::$height` to `dukt\videos\models\Video::$size::$height` $size is a `dukt\videos\models\VideoSize` instance
- Moved `dukt\videos\models\Video::$authorName` to `dukt\videos\models\Video::$author::$name` $author is a `dukt\videos\models\VideoAuthor` instance
- Moved `dukt\videos\models\Video::$authorUrl` to `dukt\videos\models\Video::$author::$url` $author is a `dukt\videos\models\VideoAuthor` instance
- Moved `dukt\videos\models\Video::$authorUsername` to `dukt\videos\models\Video::$author::$name` $author is a `dukt\videos\models\VideoAuthor` instance
- Moved `dukt\videos\models\Video::$gatewayName` to `dukt\videos\models\Video::$gateway::getName()` $gateway is a `dukt\videos\base\Gateway` instance
- Moved `dukt\videos\models\Video::$thumbnailSource` to `dukt\videos\models\Video::$thumbnail::$smallestSourceUrl`
- Moved `dukt\videos\models\Video::$thumbnailLargeSource` to `dukt\videos\models\Video::$thumbnail::$largestSourceUrl`
- Moved `dukt\videos\models\Video::$durationSeconds` to `dukt\videos\models\Video::$duration` $duration is a `\DateInterval` instance
- Moved `dukt\videos\models\Video::$duration8601` to `dukt\videos\models\Video::$duration` $duration is a `\DateInterval` instance
- Removed `dukt\videos\models\Video::getDuration()` use `dukt\videos\models\Video::$duration` instead ; $duration is a `\DateInterval` instance (use DateTimeHelper to format and twig filter in template)
- Changed `dukt\videos\models\Video::getGateway()` returns `dukt\videos\base\Gateway` instance or throw `dukt\videos\errors\GatewayNotFoundException` if not found
- Moved `dukt\videos\models\Video::getEmbed()` to `dukt\videos\models\Video::getEmbedHtml()` and it returns `\Twig\Markup`
- Moved `dukt\videos\models\Video::getThumbnail()` to `dukt\videos\models\VideoThumbnail::getUrl()`
- Removed `dukt\videos\helpers\VideosHelper`
- Moved `dukt\videos\helpers\VideosHelper::getDuration()` to `dukt\videos\helpers\DateTimeHelper::formatDateIntervalToReadable()`
- Moved `dukt\videos\helpers\VideosHelper::getDuration8601()` to `dukt\videos\helpers\DateTimeHelper::formatDateIntervalToISO8601()`
- Moved `dukt\videos\helpers\VideosHelper::getVideoThumbnail()` to `dukt\videos\helpers\ThumbnailHelper::getByVideoAndSize()`
- Changed `dukt\videos\services\Cache::get()` need to be called with cachekey param
- Changed `dukt\videos\services\Cache::set()` need to be called with cachekey param ; can’t override plugin cache settings anymore
- Moved `dukt\videos\services\Gateways::EVENT_REGISTER_GATEWAY_TYPES` to `dukt\videos\events\RegisterGatewayTypesEvent::NAME`
- `dukt\videos\services\Gateways::getGateways()` param to check enabled status has null default value
- Moved `dukt\videos\services\Gateways::getGateway()` to dukt\videos\services\Gateways::getGatewayByHandle()` and param to check enabled status has null default value ; throw `dukt\videos\errors\GatewayNotFoundException` if no gateway was found with given gateway handle
- `dukt\videos\services\Oauth` is now fully in charge of token management
- Moved `dukt\videos\services\Oauth::getToken()` to `dukt\videos\services\Oauth::getOauthAccessTokenByGateway()`
- Moved `dukt\videos\services\Oauth::saveToken()` to `dukt\videos\services\Oauth::saveOauthAccessTokenByGateway()`
- Moved `dukt\videos\services\Oauth::deleteToken()` to `dukt\videos\services\Oauth::deleteOauthAccessTokenByGateway()`
- Moved `dukt\videos\Plugin::getOauthProviderOptions()` to `dukt\videos\services\Oauth::getOauthProviderOptions()`
- Removed `dukt\videos\base\PluginTrait::getTokens()`
- Removed `dukt\videos\services\Tokens`
- Removed `dukt\videos\models\Token`
- Changed `dukt\videos\services\Videos::getVideoByUrl()` signature: no more cache management inside ; throw `dukt\videos\errors\VideoNotFoundException` if no video was found with the given url
- Removed `dukt\videos\services\Videos::getEmbed()` use `dukt\videos\models\Video::getEmbedHtml()` or dukt\videos\web\twig\variables\VideosVariable::getEmbedHtml() (in twig template) instead
- Removed `dukt\videos\services\Videos::getVideoById()` use `dukt\videos\base\Gateway::getVideoById()` instead
- Changed `dukt\videos\web\twig\variables\VideosVariable::getVideoByUrl()` signature: no more cache management inside
- Changed `dukt\videos\web\twig\variables\VideosVariable::url()` signature: no more cache management inside

### Fixed
- Fixed a bug where Vimeo video listing might not be loaded properly when the plugin was unable to find one of the videos’ thumbnail.

## 2.0.15 - 2021-05-19

### Changed
- The plugin’s icon has been updated.

### Fixed
- Fixed a bug where Vimeo thumbnail generation could fail due to Vimeo not providing a file with an extension, resulting in an exception for installs using the GD image driver. ([#40](https://github.com/dukt/videos/issues/40), [#54](https://github.com/dukt/videos/issues/54))

## 2.0.14 - 2021-04-08

### Added
- Added environment variable suggestions support for the OAuth client ID and secret.
- Added a link to the documentation in the OAuth settings for video providers.

### Changed
- The `dukt\videos\services\Videos::requestVideoById()` method now takes into account Videos’ `enableCache` config.

### Fixed
- Fixed a bug where the plugin was using a medium quality image for generating thumbnails, resulting in low quality thumbnails. ([#48](https://github.com/dukt/videos/issues/48))

## 2.0.13 - 2021-02-10

### Changed
- Updated `league/oauth2-client` to 2.5.

### Fixed
- Fixed a bug where the environment variables were not being parsed when used for client ID or secret OAuth configuration.
- Fixed a bug where video thumbnails could not be saved due to an issue with Guzzle 7. ([#49](https://github.com/dukt/videos/issues/49))

## 2.0.12 - 2020-09-25

### Changed
- Videos now requires Craft CMS 3.5.0 or above.

### Fixed
- Fixed `m190601_092217_tokens` migration that was causing issues during Craft 2 to Craft 3 upgrade. ([#32](https://github.com/dukt/videos/issues/32), [#44](https://github.com/dukt/videos/issues/44))
- Fixed an issue where OAuth provider options were not properly formatted in the project config.

## 2.0.11 - 2020-09-18

### Added
- Added `\dukt\videos\models\Video::$duration8601`. ([#27](https://github.com/dukt/videos/pull/27))
- Added `title` embed option. ([#33](https://github.com/dukt/videos/pull/33))

### Changed
- Changed the maximum number of YouTube playlists from 5 to 50. ([#28](https://github.com/dukt/videos/issues/28))
- Deprecated `\dukt\videos\models\Video::$thumbnailLargeSource`, use `\dukt\videos\models\Video::$thumbnailSource` instead. ([#37](https://github.com/dukt/videos/issues/37))

### Fixed
- Fixed the styles of the explorer's sidebar.

## 2.0.10 - 2019-06-05

### Fixed
- Fixed a bug where migration `m190601_092217_tokens` could fail when `allowAdminChanges` was to `false`. ([#22](https://github.com/dukt/videos/issues/22), [#23](https://github.com/dukt/videos/issues/23))

## 2.0.9 - 2019-06-03

### Changed
- Updated schema version to 1.0.2.

## 2.0.8 - 2019-06-02

### Added
- Added environment variables support for gateways’s OAuth client ID and secret in a project config context. ([#18](https://github.com/dukt/videos/issues/18))

### Changed
- OAuth tokens are now stored in their own database table instead of being stored in the plugin’s settings. ([#14](https://github.com/dukt/videos/issues/14), [#21](https://github.com/dukt/videos/issues/21))

### Fixed
- Fixed a bug where the YouTube gateway was not explicitly prompting for consent, which could cause the token to be saved without a refresh token.
- Fixed a bug that prevented YouTube thumbnails from working properly for private videos. ([#17](https://github.com/dukt/videos/issues/17))

## 2.0.7 - 2019-05-15

### Fixed 
- Fixed a bug where search keywords were not properly encoded to support emojis when saving a video. ([#20](https://github.com/dukt/videos/issues/20))

## 2.0.6 - 2019-03-29

### Changed
- Updated `league/oauth2-google` dependency to `^3.0`. 

## 2.0.5 - 2019-03-03

### Fixed
- Fixed a bug where thumbnails for YouTube videos were not cropped properly.

## 2.0.4 - 2018-09-10

### Fixed
- Fixed a bug where the Video field was not properly migrated when upgrading from Craft 2 to Craft 3.

## 2.0.3 - 2018-09-03

### Fixed
- Fixed a bug where Vimeo videos with custom URLs couldn’t be selected in the explorer.

## 2.0.2 - 2018-06-28

### Changed
- Replaced `dukt/oauth2-google` dependency with `league/oauth2-google`.
- Removed `dukt\videos\services\Videos::isOauthProviderConfigured()`.

### Fixed
- Fixed a bug which prevented the `oauthProviderOptions` config from being set from a config file for some providers and from the plugin’s stored settings for other providers.
- Fixed a bug where videos wouldn’t automatically start to play when clicking on the play button.

## 2.0.1 - 2018-05-26

### Added
- The videos explorer is now showing a spinner while it’s loading.

### Fixed
- Fixed a scrolling bug in the Videos explorer modal.

## 2.0.0 - 2018-05-09

### Added
- Show account details on the gateway details page.
- Added `files` to the list of fields requested for a Vimeo video.
- Added the ability to double click on a video so select it in a Video field scenario.

### Changed
- Removed unused `dukt\videos\base\Gateway::setAuthenticationToken()` method.
- Stopped catching exceptions in the `dukt\videos\base\Gateway::hasToken()` method.
- Improved exception handling when OAuth callback fails.

### Fixed
- Fixed a bug where `dukt\videos\services\Oauth::getTokenData()` could return a string instead of an array. ([#7](https://github.com/dukt/videos/issues/7))

## 2.0.0-beta.7 - 2018-04-27

### Changed
- Updated dukt/oauth2-vimeo dependency to `^2.0.1`.

### Fixed
- Fixed namespacing bug in `dukt\videos\services\Cache`. ([#4](https://github.com/dukt/videos/issues/4))
- Fixed a bug where the explorer modal’s spinner was not properly positionned.
- Fixed authentication bug with Vimeo.

## 2.0.0-beta.6 - 2017-12-17

### Changed
- Updated to require craftcms/cms `^3.0.0-RC1`.
- Updated plugin icon.

### Fixed
- Fixed layout bug with the video explorer.

### Removed
- Removed ununsed mask icon.

## 2.0.0-beta.5 - 2017-09-24

### Added
- Added the `registerGatewayTypes` to `dukt\videos\services\Gateways`, giving plugins a chance to register gateway types (replacing `getVideosGateways()`).
- Added `dukt\videos\events\RegisterGatewayTypesEvent`.

### Improved
- Now using the `craft\web\twig\variables\CraftVariable`’s `init` event to register Videos’ variable class, replacing the now-deprecated `defineComponents`.
- Removed `dukt\videos\Plugin::getVideosGateways()`.

## 2.0.0-beta.4 - 2017-09-22

### Changed
- The plugin now requires Craft 3.0.0-beta.27 or above.

### Fixed 
- Fixed video thumbnails for Craft 3.0.0-beta.27 and above where resource URLs are not supported anymore.

## 2.0.0-beta.3 - 2017-08-28

### Fixed

- Fixed `dukt\videos\fields\Video` to use `normalizeValue()`. ([#2](https://github.com/dukt/videos/issues/2))

## 2.0.0-beta.2 - 2017-08-28

### Added

- Added `dukt\videos\services\Oauth::getTokenData()`.

### Improved

- Check that there is an `expires` value before trying to refresh the token in `dukt\videos\base\Gateway::createTokenFromData()`.
- Moved `dukt\videos\base\Gateway::createTokenFromData()` to `dukt\videos\services\Oauth::createTokenFromData()`.
- Renamed `dukt\videos\base\Gateway::getToken()` to `getOauthToken()`.
- Instantiating video gateways doesn’t require a refreshed token anymore.
- Improved error handling for the settings index page.
- Improved error handling for the gateway details page.
- Replaced `dukt\videos\base\Gateway::parseJson()` with `craft\helpers\Json::decode()`.
- Replaced `dukt\videos\fields\Video::prepValue()` with `normalizeValue()`. ([#1](https://github.com/dukt/videos/issues/1))

### Fixed

- Fixed a bug where `dukt\videos\services\Oauth::getToken()` would crash if the token didn’t exists for the given gateway.


## 2.0.0-beta.1 - 2017-08-25

### Added

- Craft 3 compatibility.
- Added `review_link` to the list of fields returned by the Vimeo API for a video.
- Added YouTube and Vimeo SVG icons
- Added “Like videos” support for the YouTube gateway.
- Added `dukt\videos\base\Gateway::getJavascriptOrigin()`.
- Added `dukt\videos\base\Gateway::getOauthProviderName()`.
- Added `dukt\videos\base\Gateway::getRedirectUri()`.
- Added `dukt\videos\base\Gateway::getVideosPerPage()`.
- Added `dukt\videos\base\GatewayInterface::createOauthProvider()`.
- Added `dukt\videos\base\GatewayInterface::getIconAlias()`.
- Added `dukt\videos\base\GatewayInterface::getOauthProviderApiConsoleUrl()`.
- Added `dukt\videos\base\PluginTrait`.
- Added `dukt\videos\errors\ApiResponseException`.
- Added `dukt\videos\errors\CollectionParsingException`.
- Added `dukt\videos\errors\GatewayMethodNotFoundException`.
- Added `dukt\videos\errors\GatewayNotFoundException`.
- Added `dukt\videos\errors\JsonParsingException`.
- Added `dukt\videos\errors\VideoNotFoundException`.
- Added `dukt\videos\models\Settings`.
- Added `dukt\videos\web\assets\settings\SettingsAsset`.
- Added `dukt\videos\web\assets\videofield\VideoFieldAsset`.
- Added `dukt\videos\web\assets\videos\VideosAsset`.

### Changed

- OAuth provider options are now using gateway’s handle instead of oauth provider’s handle as a key.
- Removed dependency with `dukt/oauth`
- Search support is disabled by default and gateways can enable it by defining a `supportsSearch()` method returning `true`.
- Moved `dukt\videos\controllers\VideosController::actionFieldPreview()` to `dukt\videos\controllers\ExplorerController::actionFieldPreview()`.
- Moved `dukt\videos\controllers\VideosController::actionPlayer()` to `dukt\videos\controllers\ExplorerController::actionPlayer()`.
- Removed `Craft\Videos_InstallController`.
- Removed `Craft\VideosController`.
- Removed `dukt\videos\models\Settings::$youtubeParameters`.
- Renamed `Craft\Videos_CacheService` to `dukt\videos\services\Cache`.
- Renamed `Craft\Videos_CollectionModel` to `dukt\videos\models\Collection`.
- Renamed `Craft\Videos_GatewaysService` to `dukt\videos\services\Gateways`.
- Renamed `Craft\Videos_OauthController` to `dukt\videos\controllers\OauthController`.
- Renamed `Craft\Videos_OauthService` to `dukt\videos\services\Oauth`.
- Renamed `Craft\Videos_SectionModel` to `dukt\videos\models\Section`.
- Renamed `Craft\Videos_SettingsController` to `dukt\videos\controllers\SettingsController`.
- Renamed `Craft\Videos_VideoFieldType` to `dukt\videos\fields\Video`.
- Renamed `Craft\Videos_VideoModel` to `dukt\videos\models\Video`.
- Renamed `Craft\VideosController` to `dukt\videos\controllers\ExplorerController`.
- Renamed `Craft\VideosHelper` to `dukt\videos\helpers\VideosHelper`.
- Renamed `Craft\VideosService` to `dukt\videos\services\Videos`.
- Renamed `Craft\VideosVariable` to `dukt\videos\web\twig\variables\VideosVariable`.
- Renamed `dukt\videos\base\Gateway::apiGet()` to `get()`.
- Renamed `dukt\videos\base\Gateway::authenticationSetToken()` to `setAuthenticationToken()`.
- Renamed `Dukt\Videos\Gateways\BaseGateway` to `dukt\videos\base\Gateway`.
- Renamed `Dukt\Videos\Gateways\IGateway` to `dukt\videos\base\GatewayInterface`.
- Renamed `Dukt\Videos\Gateways\Vimeo` to `dukt\videos\gateways\Vimeo`.
- Renamed `Dukt\Videos\Gateways\Youtube` to `dukt\videos\gateways\YouTube`.


### Fixed

- Fixed a bug where token when not being properly refreshed in `dukt\videos\services\Gateways::loadGateways()`.
- Fixed success message when connecting to Vimeo.
- Fixed Vimeo’s console API URL.
- Fixed YouTube’s OAuth provider API console URL.
