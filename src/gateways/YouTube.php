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
use dukt\videos\models\Collection;
use dukt\videos\models\Section;
use dukt\videos\models\Video;
use dukt\videos\models\VideoAuthor;
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
     * @return array
     * @throws \dukt\videos\errors\ApiResponseException
     *
     * @since 2.0.0
     */
    public function getExplorerSections(): array
    {
        $sections = [];

        // Library

        $sections[] = new Section([
            'name' => 'Library',
            'collections' => [
                new Collection([
                    'name' => 'Uploads',
                    'method' => 'uploads',
                ]),
                new Collection([
                    'name' => 'Liked videos',
                    'method' => 'likes',
                ]),
            ],
        ]);

        // Playlists

        $playlists = $this->getCollectionsPlaylists();

        $collections = [];

        foreach ($playlists as $playlist) {
            $collections[] = new Collection([
                'name' => $playlist['title'],
                'method' => 'playlist',
                'options' => ['id' => $playlist['id']],
            ]);
        }

        if (\count($collections) > 0) {
            $sections[] = new Section([
                'name' => 'Playlists',
                'collections' => $collections,
            ]);
        }

        return $sections;
    }

    /**
     * Returns a list of liked videos.
     *
     * @param array $params
     *
     * @return array
     *
     * @throws \dukt\videos\errors\ApiResponseException
     */
    protected function getVideosLikes(array $params = []): array
    {
        $query = [];
        $query['part'] = 'snippet,statistics,contentDetails';
        $query['myRating'] = 'like';
        $query = array_merge($query, $this->paginationQueryFromParams($params));

        $videosResponse = $this->fetch('videos', ['query' => $query]);

        $videos = $this->_parseVideos($videosResponse['items']);

        return array_merge([
            'videos' => $videos,
        ], $this->paginationResponse($videosResponse, $videos));
    }

    /**
     * Returns a list of videos in a playlist.
     *
     * @param array $params
     *
     * @return array
     *
     * @throws \dukt\videos\errors\ApiResponseException
     */
    protected function getVideosPlaylist(array $params = []): array
    {
        // Get video IDs from playlist items

        $videoIds = [];

        $query = [];
        $query['part'] = 'id,snippet';
        $query['playlistId'] = $params['id'];
        $query = array_merge($query, $this->paginationQueryFromParams($params));

        $playlistItemsResponse = $this->fetch('playlistItems', ['query' => $query]);

        foreach ($playlistItemsResponse['items'] as $item) {
            $videoId = $item['snippet']['resourceId']['videoId'];
            $videoIds[] = $videoId;
        }

        // Get videos from video IDs

        $query = [];
        $query['part'] = 'snippet,statistics,contentDetails';
        $query['id'] = implode(',', $videoIds);

        $videosResponse = $this->fetch('videos', ['query' => $query]);
        $videos = $this->_parseVideos($videosResponse['items']);

        return array_merge([
            'videos' => $videos,
        ], $this->paginationResponse($playlistItemsResponse, $videos));
    }

    /**
     * Returns a list of videos from a search request.
     *
     * @param array $params
     *
     * @return array
     *
     * @throws \dukt\videos\errors\ApiResponseException
     */
    protected function getVideosSearch(array $params = []): array
    {
        // Get video IDs from search results
        $videoIds = [];

        $query = [];
        $query['part'] = 'id';
        $query['type'] = 'video';
        $query['q'] = $params['q'];
        $query = array_merge($query, $this->paginationQueryFromParams($params));

        $searchResponse = $this->fetch('search', ['query' => $query]);

        foreach ($searchResponse['items'] as $item) {
            $videoIds[] = $item['id']['videoId'];
        }

        // Get videos from video IDs

        if (\count($videoIds) > 0) {
            $query = [];
            $query['part'] = 'snippet,statistics,contentDetails';
            $query['id'] = implode(',', $videoIds);

            $videosResponse = $this->fetch('videos', ['query' => $query]);

            $videos = $this->_parseVideos($videosResponse['items']);

            return array_merge([
                'videos' => $videos,
            ], $this->paginationResponse($searchResponse, $videos));
        }

        return [];
    }

    /**
     * Returns a list of uploaded videos.
     *
     * @param array $params
     *
     * @return array
     *
     * @throws \dukt\videos\errors\ApiResponseException
     */
    protected function getVideosUploads(array $params = []): array
    {
        $uploadsPlaylistId = $this->getSpecialPlaylistId('uploads');

        if (!$uploadsPlaylistId) {
            return [];
        }

        // Retrieve video IDs

        $query = [];
        $query['part'] = 'id,snippet';
        $query['playlistId'] = $uploadsPlaylistId;
        $query = array_merge($query, $this->paginationQueryFromParams($params));

        $playlistItemsResponse = $this->fetch('playlistItems', ['query' => $query]);

        $videoIds = [];

        foreach ($playlistItemsResponse['items'] as $item) {
            $videoId = $item['snippet']['resourceId']['videoId'];
            $videoIds[] = $videoId;
        }

        // Retrieve videos from video IDs

        $query = [];
        $query['part'] = 'snippet,statistics,contentDetails,status';
        $query['id'] = implode(',', $videoIds);

        $videosResponse = $this->fetch('videos', ['query' => $query]);

        $videos = $this->_parseVideos($videosResponse['items']);

        return array_merge([
            'videos' => $videos,
        ], $this->paginationResponse($playlistItemsResponse, $videos));
    }

    /**
     * @return array
     *
     * @throws \dukt\videos\errors\ApiResponseException
     */
    private function getCollectionsPlaylists(): array
    {
        $data = $this->fetch('playlists', [
            'query' => [
                'part' => 'snippet',
                'mine' => 'true',
                'maxResults' => 50,
            ],
        ]);

        return $this->parseCollections($data['items']);
    }

    /**
     * @return null|mixed
     *
     * @throws \dukt\videos\errors\ApiResponseException
     */
    private function getSpecialPlaylists()
    {
        $channelsQuery = [
            'part' => 'contentDetails',
            'mine' => 'true',
        ];

        $channelsResponse = $this->fetch('channels', ['query' => $channelsQuery]);

        if (isset($channelsResponse['items'][0])) {
            $channel = $channelsResponse['items'][0];

            return $channel['contentDetails']['relatedPlaylists'];
        }

        return null;
    }

    /**
     * Retrieves playlist ID for special playlists of type: likes, favorites, uploads.
     *
     * @param string $type
     *
     * @return null|mixed
     *
     * @throws \dukt\videos\errors\ApiResponseException
     */
    private function getSpecialPlaylistId(string $type)
    {
        $specialPlaylists = $this->getSpecialPlaylists();

        if (isset($specialPlaylists[$type])) {
            return $specialPlaylists[$type];
        }

        return null;
    }

    /**
     * @param array $params
     *
     * @return array
     */
    private function paginationQueryFromParams(array $params = []): array
    {
        // Pagination

        $pagination = [
            'page' => 1,
            'perPage' => $this->getVideosPerPage(),
            'moreToken' => false,
        ];

        if (!empty($params['perPage'])) {
            $pagination['perPage'] = $params['perPage'];
        }

        if (!empty($params['moreToken'])) {
            $pagination['moreToken'] = $params['moreToken'];
        }

        // Query

        $query = [];
        $query['maxResults'] = $pagination['perPage'];

        if (!empty($pagination['moreToken'])) {
            $query['pageToken'] = $pagination['moreToken'];
        }

        return $query;
    }

    /**
     * @param $response
     * @param $videos
     *
     * @return array
     */
    private function paginationResponse($response, $videos): array
    {
        $more = false;

        if (!empty($response['nextPageToken']) && \count($videos) > 0) {
            $more = true;
        }

        return [
            'prevPage' => $response['prevPageToken'] ?? null,
            'moreToken' => $response['nextPageToken'] ?? null,
            'more' => $more,
        ];
    }

    /**
     * @param $item
     *
     * @return array
     */
    private function parseCollection($item): array
    {
        $collection = [];
        $collection['id'] = $item['id'];
        $collection['title'] = $item['snippet']['title'];
        $collection['totalVideos'] = 0;
        $collection['url'] = 'title';

        return $collection;
    }

    /**
     * @param $items
     *
     * @return array
     */
    private function parseCollections($items): array
    {
        $collections = [];

        foreach ($items as $item) {
            $collection = $this->parseCollection($item);
            $collections[] = $collection;
        }

        return $collections;
    }

    /**
     * Parses videos from data.
     *
     * @param array $data
     * @return Video[]
     */
    private function _parseVideos(array $data): array
    {
        $videos = [];

        foreach ($data as $videoData) {
            $videos[] = $this->_parseVideo($videoData);
        }

        return $videos;
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
