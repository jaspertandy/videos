<?php
/**
 * @link https://dukt.net/videos/
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\gateways;

use Craft;
use DateInterval;
use DateTime;
use dukt\videos\base\Gateway;
use dukt\videos\errors\ApiClientCreateException;
use dukt\videos\errors\ApiResponseException;
use dukt\videos\errors\VideoIdExtractException;
use dukt\videos\errors\VideoNotFoundException;
use dukt\videos\models\GatewayExplorer;
use dukt\videos\models\GatewayExplorerCollection;
use dukt\videos\models\GatewayExplorerSection;
use dukt\videos\models\Video;
use dukt\videos\models\VideoAuthor;
use dukt\videos\models\VideoStatistic;
use dukt\videos\models\VideoThumbnail;
use Exception;
use GuzzleHttp\Client;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Google as GoogleProvider;

/**
 * YouTube gateway.
 *
 * @author Dukt <support@dukt.net>
 * @since 2.0.0
 */
class YouTube extends Gateway
{
    /**
     * {@inheritdoc}
     *
     * @since 2.0.0
     */
    public function getIconAlias(): string
    {
        return '@dukt/videos/icons/youtube.svg';
    }

    /**
     * {@inheritdoc}
     *
     * @since 2.0.0
     */
    public function getOauthProviderName(): string
    {
        return 'Google';
    }

    /**
     * {@inheritdoc}
     *
     * @since 2.0.0
     */
    public function getOauthProviderOptions(bool $parseEnv = true): array
    {
        $options = parent::getOauthProviderOptions($parseEnv);

        if (isset($options['useOidcMode']) === false) {
            $options['useOidcMode'] = true;
        }

        return $options;
    }

    /**
     * {@inheritdoc}
     *
     * @since 2.0.0
     */
    public function createOauthProvider(array $options): AbstractProvider
    {
        return new GoogleProvider($options);
    }

    /**
     * {@inheritdoc}
     *
     * @since 2.0.0
     */
    public function getOauthScope(): array
    {
        return [
            'https://www.googleapis.com/auth/userinfo.profile',
            'https://www.googleapis.com/auth/userinfo.email',
            'https://www.googleapis.com/auth/youtube',
            'https://www.googleapis.com/auth/youtube.readonly',
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @since 2.0.0
     */
    public function getOauthAuthorizationOptions(): array
    {
        return [
            'access_type' => 'offline',
            'prompt' => 'consent',
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @since 2.0.0
     */
    public function getOauthProviderApiConsoleUrl(): string
    {
        return 'https://console.developers.google.com/';
    }

    /**
     * {@inheritdoc}
     *
     * @since 3.0.0
     */
    public function extractVideoIdFromVideoUrl(string $videoUrl): string
    {
        $regexp = '/^https?:\/\/(www\.youtube\.com|youtube\.com|youtu\.be).*\/(watch\?v=)?(.*)/';

        if (preg_match($regexp, $videoUrl, $matches, PREG_OFFSET_CAPTURE) > 0) {
            $videoId = $matches[3][0];

            // fixes the youtube &feature_gdata bug
            if (strpos($videoId, '&')) {
                $videoId = substr($videoId, 0, strpos($videoId, '&'));
            }

            return $videoId;
        }

        throw new VideoIdExtractException(Craft::t('videos', 'Extract ID from URL {videoUrl} on {gatewayName} not working.', ['videoUrl' => $videoUrl, 'gatewayName' => $this->getName()]));
    }

    /**
     * {@inheritdoc}
     *
     * @since 3.0.0
     */
    public function createApiClient(): Client
    {
        try {
            $options = [
                'base_uri' => 'https://www.googleapis.com/youtube/v3/',
                'headers' => [
                    'Authorization' => 'Bearer '.$this->getOauthAccessToken()->getToken(),
                ],
            ];

            return new Client($options);
        } catch (Exception $e) {
            // log exception
            Craft::error($e->getMessage(), __METHOD__);

            throw new ApiClientCreateException(Craft::t('videos', 'An occured during creation of API client for {gatewayName}.', ['gatewayName' => $this->getName()]), 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @since 3.0.0
     */
    public function fetchVideoById(string $videoId): Video
    {
        try {
            $data = $this->fetch('videos', [
                'query' => [
                    'part' => 'snippet,statistics,contentDetails',
                    'id' => $videoId,
                ],
            ]);

            if (count($data['items']) !== 1) {
                throw new VideoNotFoundException(Craft::t('videos', 'Fetch video with ID {videoId} on {gatewayName} not working.', ['videoId' => $videoId, 'gatewayName' => $this->getName()]));
            }

            return $this->_parseVideo($data['items'][0]);
        } catch (Exception $e) {
            // log exception
            Craft::error($e->getMessage(), __METHOD__);

            throw new VideoNotFoundException(Craft::t('videos', 'Fetch video with ID {videoId} on {gatewayName} not working.', ['videoId' => $videoId, 'gatewayName' => $this->getName()]), 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @since 2.0.0
     */
    public function getEmbedUrlFormat(): string
    {
        return 'https://www.youtube.com/embed/%s?wmode=transparent';
    }

    /**
     * {@inheritdoc}
     *
     * @since 2.0.0
     */
    public function supportsSearch(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @since 3.0.0
     */
    public function getExplorer(): GatewayExplorer
    {
        $explorer = new GatewayExplorer();

        // library section
        $explorer->sections[] = new GatewayExplorerSection([
            'name' => Craft::t('videos', 'explorer.section.library.title'),
            'collections' => [
                new GatewayExplorerCollection([
                    'name' => Craft::t('videos', 'explorer.collection.upload.title'),
                    'method' => 'uploads',
                    'icon' => 'video-camera',
                ]),
                new GatewayExplorerCollection([
                    'name' => Craft::t('videos', 'explorer.collection.like.title'),
                    'method' => 'likes',
                    'icon' => 'thumb-up',
                ]),
            ],
        ]);

        // playlists section
        try {
            $playlistsData = $this->_fetchPlaylists();

            if (count($playlistsData) > 0) {
                $section = new GatewayExplorerSection([
                    'name' => Craft::t('videos', 'explorer.section.playlist.title'),
                ]);

                foreach ($playlistsData as $playlistData) {
                    $section->collections[] = new GatewayExplorerCollection([
                        'name' => $playlistData['snippet']['title'],
                        'method' => 'playlist',
                        'options' => ['id' => $playlistData['id']],
                        'icon' => 'list',
                    ]);
                }

                $explorer->sections[] = $section;
            }
        } catch (ApiResponseException $e) {
            // log exception
            Craft::error($e->getMessage(), __METHOD__);
        }

        return $explorer;
    }

    /**
     * Returns a list of uploaded videos.
     *
     * @param array $options
     * @return array
     * @throws ApiResponseException
     *
     * @since 2.0.0
     */
    protected function getVideosUploads(array $options = []): array
    {
        // fetch channel's uploaded playlist ID
        $query = [
            'part' => 'contentDetails',
            'mine' => 'true',
        ];

        $channelData = $this->fetch('channels', ['query' => $query]);

        if (
            empty($channelData['items']) === true
            || count($channelData['items']) !== 1
            || empty($channelData['items'][0]['contentDetails']['relatedPlaylists']['uploads']) === true
        ) {
            return [];
        }

        $options['id'] = $channelData['items'][0]['contentDetails']['relatedPlaylists']['uploads'];

        // fetch videos by playlist ID
        return $this->getVideosPlaylist($options);
    }

    /**
     * Returns a list of liked videos.
     *
     * @param array $options
     * @return array
     * @throws ApiResponseException
     *
     * @since 2.0.0
     */
    protected function getVideosLikes(array $options = []): array
    {
        $query = [
            'part' => 'snippet,statistics,contentDetails',
            'myRating' => 'like',
        ];

        $videosData = $this->fetch('videos', [
            'query' => array_merge($query, $this->_createPaginationQuery($options)),
        ]);

        $videos = $this->_parseVideos($videosData['items']);

        return [
            'videos' => $videos,
            'pagination' => $this->_createPaginationNav($videosData),
        ];
    }

    /**
     * Returns a list of videos in a playlist.
     *
     * @param array $options
     * @return array
     * @throws ApiResponseException
     *
     * @since 2.0.0
     */
    protected function getVideosPlaylist(array $options = []): array
    {
        // fetch video IDs from a playlist
        $query = [
            'part' => 'id,snippet',
            'playlistId' => $options['id'],
        ];

        $playlistItemsData = $this->fetch('playlistItems', [
            'query' => array_merge($query, $this->_createPaginationQuery($options)),
        ]);

        // fetch videos from video IDs
        $videoIds = array_map(function (array $itemData) {
            return $itemData['snippet']['resourceId']['videoId'];
        }, $playlistItemsData['items']);

        $videosData = $this->_fetchVideosByIds($videoIds);
        $videos = $this->_parseVideos($videosData);

        return [
            'videos' => $videos,
            'pagination' => $this->_createPaginationNav($playlistItemsData),
        ];
    }

    /**
     * Returns a list of videos from a search request.
     *
     * @param array $options
     * @return array
     * @throws ApiResponseException
     *
     * @since 2.0.0
     */
    protected function getVideosSearch(array $options = []): array
    {
        // fetch video IDs from a search results
        $query = [
            'part' => 'id',
            'type' => 'video',
            'q' => $options['q'],
        ];

        $searchData = $this->fetch('search', [
            'query' => array_merge($query, $this->_createPaginationQuery($options)),
        ]);

        // fetch videos from video IDs
        $videoIds = array_map(function (array $itemData) {
            return $itemData['id']['videoId'];
        }, $searchData['items']);

        $videosData = $this->_fetchVideosByIds($videoIds);
        $videos = $this->_parseVideos($videosData);

        return [
            'videos' => $videos,
            'pagination' => $this->_createPaginationNav($searchData),
        ];
    }

    /**
     * Creates pagination query.
     *
     * @param array $options
     * @return array
     */
    private function _createPaginationQuery(array $options = []): array
    {
        $query = [
            'maxResults' => (empty($options['perPage']) === false) ? (int)$options['perPage'] : $this->getVideosPerPage(),
        ];

        if (empty($options['moreToken']) === false) {
            $query['pageToken'] = $options['moreToken'];
        }

        return $query;
    }

    /**
     * Creates pagination nav.
     *
     * @param array $data
     * @return array
     */
    private function _createPaginationNav(array $data = []): array
    {
        return [
            'moreToken' => $data['nextPageToken'] ?? null,
            'more' => isset($data['nextPageToken']) === true ? true : false,
        ];
    }

    /**
     * Fetches videos by IDs from API.
     *
     * @param array $videoIds
     * @return array
     * @throws ApiResponseException
     */
    private function _fetchVideosByIds(array $videoIds = []): array
    {
        if (count($videoIds) === 0) {
            return [];
        }

        $query = [
            'part' => 'snippet,statistics,contentDetails',
            'id' => implode(',', $videoIds),
        ];

        $data = $this->fetch('videos', ['query' => $query]);

        return $data['items'];
    }

    /**
     * Fetches playlists from API.
     *
     * @return array
     * @throws ApiResponseException
     */
    private function _fetchPlaylists(): array
    {
        $data = $this->fetch('playlists', [
            'query' => [
                'part' => 'snippet',
                'mine' => 'true',
                'maxResults' => 50,
            ],
        ]);

        return $data['items'];
    }

    /**
     * Parses videos from data.
     *
     * @param array $data
     * @return Video[]
     */
    private function _parseVideos(array $data): array
    {
        return array_map(function (array $videoData) {
            return $this->_parseVideo($videoData);
        }, $data);
    }

    /**
     * Parses video from data.
     *
     * @param array $data
     * @return Video
     */
    private function _parseVideo(array $data): Video
    {
        $video = new Video();
        $video->id = $data['id'];
        $video->url = 'http://youtu.be/'.$video->id;
        $video->title = $data['snippet']['title'];
        $video->description = $data['snippet']['description'];
        $video->duration = new DateInterval($data['contentDetails']['duration']);
        $video->publishedAt = new DateTime($data['snippet']['publishedAt']);
        $video->gatewayHandle = $this->getHandle();
        $video->raw = $data;

        // author
        $author = new VideoAuthor();
        $author->setVideo($video);
        $author->name = $data['snippet']['channelTitle'];
        $author->url = 'http://youtube.com/channel/'.$data['snippet']['channelId'];

        // thumbnail
        $thumbnail = new VideoThumbnail();
        $thumbnail->setVideo($video);
        $this->_populateThumbnail($thumbnail, $data);

        // statistic
        $statistic = new VideoStatistic();
        $statistic->setVideo($video);
        $statistic->playCount = (int)$data['statistics']['viewCount'];

        // privacy
        if (empty($data['status']['privacyStatus']) === false && $data['status']['privacyStatus'] === 'private') {
            $video->private = true;
        }

        return $video;
    }

    /**
     * Populate the thumbnail.
     *
     * @param VideoThumbnail $thumbnail
     * @param array $data
     * @return void
     */
    private function _populateThumbnail(VideoThumbnail $thumbnail, array $data): void
    {
        $smallestSize = 0;
        $largestSize = 0;

        if (empty($data['snippet']['thumbnails']['maxres']) === false) {
            $thumbnail->smallestSourceUrl = $data['snippet']['thumbnails']['maxres']['url'];
            $smallestSize = (int)$data['snippet']['thumbnails']['maxres']['width'];
            $thumbnail->largestSourceUrl = $data['snippet']['thumbnails']['maxres']['url'];
            $largestSize = (int)$data['snippet']['thumbnails']['maxres']['width'];
        }

        foreach ($data['snippet']['thumbnails'] as $thumbnailData) {
            if ($smallestSize === 0 || (int)$thumbnailData['width'] < $smallestSize) {
                if ((int)$thumbnailData['width'] >= 300) {
                    $smallestSize = (int)$thumbnailData['width'];
                    $thumbnail->smallestSourceUrl = $thumbnailData['url'];
                }
            }

            if ((int)$thumbnailData['width'] > $largestSize) {
                $largestSize = (int)$thumbnailData['width'];
                $thumbnail->largestSourceUrl = $thumbnailData['url'];
            }
        }
    }
}
