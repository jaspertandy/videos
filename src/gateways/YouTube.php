<?php
/**
 * @link https://dukt.net/videos/
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\gateways;

use DateInterval;
use DateTime;
use dukt\videos\base\Gateway;
use dukt\videos\errors\ApiClientCreateException;
use dukt\videos\errors\VideoIdExtractException;
use dukt\videos\errors\VideoNotFoundException;
use dukt\videos\models\Section;
use dukt\videos\models\Video;
use dukt\videos\models\VideoAuthor;
use dukt\videos\models\VideoExplorer;
use dukt\videos\models\VideoExplorerCollection;
use dukt\videos\models\VideoExplorerSection;
use dukt\videos\models\VideoStatistic;
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
    public function getName(): string
    {
        return 'YouTube';
    }

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

        throw new VideoIdExtractException(/* TODO: more precise message */);
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
            throw new ApiClientCreateException(/* TODO: more precise message */);
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
                throw new VideoNotFoundException(/* TODO: more precise message */);
            }

            return $this->_parseVideo($data['items'][0]);
        } catch (Exception $e) {
            throw new VideoNotFoundException(/* TODO: more precise message */);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @since 2.0.0
     */
    public function getEmbedFormat(): string
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
    public function getExplorer(): VideoExplorer
    {
        $explorer = new VideoExplorer();

        // library section
        $explorer->sections[] = new VideoExplorerSection([
            'name' => 'Library',
            'collections' => [
                new VideoExplorerCollection([
                    'name' => 'Uploads',
                    'method' => 'uploads',
                    'icon' => 'video-camera',
                ]),
                new VideoExplorerCollection([
                    'name' => 'Liked videos',
                    'method' => 'likes',
                    'icon' => 'thumb-up'
                ]),
            ],
        ]);

        // playlists section
        $playlistsData = $this->_fetchPlaylists();

        if (count($playlistsData) > 0) {
            $section = new VideoExplorerSection([
                'name' => 'Playlists',
            ]);

            foreach ($playlistsData as $playlistData) {
                $section->collections[] = new VideoExplorerCollection([
                    'name' => $playlistData['snippet']['title'],
                    'method' => 'playlist',
                    'options' => ['id' => $playlistData['id']],
                    'icon' => 'list'
                ]);
            }

            $explorer->sections[] = $section;
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
        $video->thumbnailSourceUrl = $this->_getThumbnailSource($data);
        $video->gatewayHandle = $this->getHandle();
        $video->raw = $data;

        // author
        $author = new VideoAuthor();
        $author->name = $data['snippet']['channelTitle'];
        $author->url = 'http://youtube.com/channel/'.$data['snippet']['channelId'];
        $video->author = $author;

        // statistic
        $statistic = new VideoStatistic();
        $statistic->playCount = (int)$data['statistics']['viewCount'];
        $video->statistic = $statistic;

        // privacy
        if (empty($data['status']['privacyStatus']) === false && $data['status']['privacyStatus'] === 'private') {
            $video->private = true;
        }

        return $video;
    }

    /**
     * Get the thumbnail source.
     *
     * @param array $thumbnails
     * @return null|string
     */
    private function _getThumbnailSource(array $data): ?string
    {
        // need to find the largest thumbnail
        if (isset($data['snippet']['thumbnails']['maxres']) === false) {
            $largestSize = 0;
            $largestThumbnailSource = null;

            foreach ($data['snippet']['thumbnails'] as $thumbnail) {
                if ((int)$thumbnail['width'] > $largestSize) {
                    $largestThumbnailSource = $thumbnail['url'];
                    $largestSize = (int)$thumbnail['width'];
                }
            }

            return $largestThumbnailSource;
        }

        return $data['snippet']['thumbnails']['maxres']['url'];
    }
}
