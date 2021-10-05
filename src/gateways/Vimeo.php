<?php
/**
 * @link      https://dukt.net/videos/
 *
 * @copyright Copyright (c) 2021, Dukt
 * @license   https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\gateways;

use DateTime;
use Dukt\OAuth2\Client\Provider\Vimeo as VimeoProvider;
use dukt\videos\base\Gateway;
use dukt\videos\errors\CollectionParsingException;
use dukt\videos\errors\VideoIdExtractException;
use dukt\videos\errors\VideoNotFoundException;
use dukt\videos\helpers\VideosHelper;
use dukt\videos\models\Collection;
use dukt\videos\models\Section;
use dukt\videos\models\Video;
use Exception;
use GuzzleHttp\Client;
use League\OAuth2\Client\Provider\AbstractProvider;

/**
 * Vimeo gateway.
 *
 * @author Dukt <support@dukt.net>
 *
 * @since  1.0
 */
class Vimeo extends Gateway
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Vimeo';
    }

    /**
     * {@inheritdoc}
     */
    public function getIconAlias(): string
    {
        return '@dukt/videos/icons/vimeo.svg';
    }

    /**
     * {@inheritdoc}
     */
    public function createOauthProvider(array $options): AbstractProvider
    {
        return new VimeoProvider($options);
    }

    /**
     * {@inheritdoc}
     */
    public function getOauthProviderApiConsoleUrl(): string
    {
        return 'https://developer.vimeo.com/apps';
    }

    /**
     * {@inheritdoc}
     */
    public function getOauthScope(): array
    {
        return [
            'public',
            'private',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function extractVideoIdFromVideoUrl(string $videoUrl): string
    {
        $regexp = '/^https?:\/\/(www\.)?vimeo\.com\/([0-9]*)/';

        if (preg_match($regexp, $videoUrl, $matches, PREG_OFFSET_CAPTURE) > 0) {
            return $matches[2][0];
        }

        throw new VideoIdExtractException(/* TODO: more precise message */);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchVideoById(string $videoId): Video
    {
        try {
            $data = $this->get('videos/'.$videoId, [
                'query' => [
                    'fields' => 'created_time,description,duration,height,link,name,pictures,pictures,privacy,stats,uri,user,width,download,review_link,files',
                ],
            ]);

            return $this->parseVideo($data);
        } catch (Exception $e) {
            throw new VideoNotFoundException(/* TODO: more precise message */);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getEmbedFormat(): string
    {
        return 'https://player.vimeo.com/video/%s';
    }

    /**
     * {@inheritDoc}
     *
     * @return array
     *
     * @throws CollectionParsingException
     * @throws \dukt\videos\errors\ApiResponseException
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
                    'name' => 'Favorites',
                    'method' => 'favorites',
                ]),
            ],
        ]);

        // Albums

        $albums = $this->getCollectionsAlbums();

        $collections = [];

        foreach ($albums as $album) {
            $collections[] = new Collection([
                'name' => $album['title'],
                'method' => 'album',
                'options' => ['id' => $album['id']],
            ]);
        }

        if (\count($collections) > 0) {
            $sections[] = new Section([
                'name' => 'Playlists',
                'collections' => $collections,
            ]);
        }

        // channels

        $channels = $this->getCollectionsChannels();

        $collections = [];

        foreach ($channels as $channel) {
            $collections[] = new Collection([
                'name' => $channel['title'],
                'method' => 'channel',
                'options' => ['id' => $channel['id']],
            ]);
        }

        if (\count($collections) > 0) {
            $sections[] = new Section([
                'name' => 'Channels',
                'collections' => $collections,
            ]);
        }

        return $sections;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsSearch(): bool
    {
        return true;
    }

    // Protected
    // =========================================================================

    /**
     * Returns an authenticated Guzzle client.
     *
     * @return Client
     *
     * @throws \yii\base\InvalidConfigException
     */
    protected function createClient(): Client
    {
        $options = [
            'base_uri' => $this->getApiUrl(),
            'headers' => [
                'Accept' => 'application/vnd.vimeo.*+json;version='.$this->getApiVersion(),
                'Authorization' => 'Bearer '.$this->getOauthAccessToken()->getToken(),
            ],
        ];

        return new Client($options);
    }

    /**
     * Returns a list of videos in an album.
     *
     * @param array $params
     *
     * @return array
     *
     * @throws \dukt\videos\errors\ApiResponseException
     */
    protected function getVideosAlbum(array $params = []): array
    {
        $albumId = $params['id'];
        unset($params['id']);

        // albums/#album_id
        return $this->performVideosRequest('me/albums/'.$albumId.'/videos', $params);
    }

    /**
     * Returns a list of videos in a channel.
     *
     * @param array $params
     *
     * @return array
     *
     * @throws \dukt\videos\errors\ApiResponseException
     */
    protected function getVideosChannel(array $params = []): array
    {
        $params['channel_id'] = $params['id'];
        unset($params['id']);

        return $this->performVideosRequest('channels/'.$params['channel_id'].'/videos', $params);
    }

    /**
     * Returns a list of favorite videos.
     *
     * @param array $params
     *
     * @return array
     *
     * @throws \dukt\videos\errors\ApiResponseException
     */
    protected function getVideosFavorites(array $params = []): array
    {
        return $this->performVideosRequest('me/likes', $params);
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
        return $this->performVideosRequest('videos', $params);
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
        return $this->performVideosRequest('me/videos', $params);
    }

    // Private Methods
    // =========================================================================

    /**
     * @return string
     */
    private function getApiUrl(): string
    {
        return 'https://api.vimeo.com/';
    }

    /**
     * @return string
     */
    private function getApiVersion(): string
    {
        return '3.0';
    }

    /**
     * @param array $params
     *
     * @return array
     *
     * @throws CollectionParsingException
     * @throws \dukt\videos\errors\ApiResponseException
     */
    private function getCollectionsAlbums(array $params = []): array
    {
        $data = $this->get('me/albums', [
            'query' => $this->queryFromParams($params),
        ]);

        return $this->parseCollections('album', $data['data']);
    }

    /**
     * @param array $params
     *
     * @return array
     *
     * @throws CollectionParsingException
     * @throws \dukt\videos\errors\ApiResponseException
     */
    private function getCollectionsChannels(array $params = []): array
    {
        $data = $this->get('me/channels', [
            'query' => $this->queryFromParams($params),
        ]);

        return $this->parseCollections('channel', $data['data']);
    }

    /**
     * @param $type
     * @param $collections
     *
     * @return array
     *
     * @throws CollectionParsingException
     */
    private function parseCollections($type, array $collections): array
    {
        $parseCollections = [];

        foreach ($collections as $collection) {
            switch ($type) {
                case 'album':
                    $parsedCollection = $this->parseCollectionAlbum($collection);

                    break;

                case 'channel':
                    $parsedCollection = $this->parseCollectionChannel($collection);

                    break;

                default:
                    throw new CollectionParsingException('Couldn’t parse collection of type ”'.$type.'“.');
            }

            $parseCollections[] = $parsedCollection;
        }

        return $parseCollections;
    }

    /**
     * @param $data
     *
     * @return array
     */
    private function parseCollectionAlbum($data): array
    {
        $collection = [];
        $collection['id'] = substr($data['uri'], strpos($data['uri'], '/albums/') + \strlen('/albums/'));
        $collection['url'] = $data['uri'];
        $collection['title'] = $data['name'];
        $collection['totalVideos'] = $data['stats']['videos'];

        return $collection;
    }

    /**
     * @param $data
     *
     * @return array
     */
    private function parseCollectionChannel($data): array
    {
        $collection = [];
        $collection['id'] = substr($data['uri'], strpos($data['uri'], '/channels/') + \strlen('/channels/'));
        $collection['url'] = $data['uri'];
        $collection['title'] = $data['name'];
        $collection['totalVideos'] = $data['stats']['videos'];

        return $collection;
    }

    /**
     * @param $data
     *
     * @return array
     */
    private function parseVideos(array $data): array
    {
        $videos = [];

        if (!empty($data)) {
            foreach ($data as $videoData) {
                $video = $this->parseVideo($videoData);

                $videos[] = $video;
            }
        }

        return $videos;
    }

    /**
     * Parse video.
     *
     * @param array $data
     *
     * @return Video
     */
    private function parseVideo(array $data): Video
    {
        $video = new Video();
        $video->raw = $data;
        $video->authorName = $data['user']['name'];
        $video->authorUrl = $data['user']['link'];
        $video->date = new DateTime($data['created_time']);
        $video->description = $data['description'];
        $video->gatewayHandle = 'vimeo';
        $video->gatewayName = 'Vimeo';
        $video->id = (int)substr($data['uri'], \strlen('/videos/'));
        $video->plays = $data['stats']['plays'] ?? 0;
        $video->title = $data['name'];
        $video->url = 'https://vimeo.com/'.substr($data['uri'], 8);
        $video->width = $data['width'];
        $video->height = $data['height'];

        // Video duration
        $video->durationSeconds = $data['duration'];
        $video->duration8601 = VideosHelper::getDuration8601($data['duration']);

        $this->parsePrivacy($video, $data);
        $this->parseThumbnails($video, $data);

        return $video;
    }

    /**
     * Parse video’s privacy data.
     *
     * @param Video $video
     * @param array $data
     *
     * @return null
     */
    private function parsePrivacy(Video $video, array $data)
    {
        $privacyOptions = ['nobody', 'contacts', 'password', 'users', 'disable'];

        if (in_array($data['privacy']['view'], $privacyOptions, true)) {
            $video->private = true;
        }

        return null;
    }

    /**
     * Parse thumbnails.
     *
     * @param Video $video
     * @param array $data
     *
     * @return null
     */
    private function parseThumbnails(Video $video, array $data)
    {
        if (!\is_array($data['pictures'])) {
            return null;
        }

        $largestSize = 0;

        foreach ($this->getVideoDataPictures($data, 'thumbnail') as $picture) {
            // Retrieve highest quality thumbnail
            if ($picture['width'] > $largestSize) {
                $video->thumbnailSource = $picture['link'];
                $largestSize = $picture['width'];
            }
        }

        $video->thumbnailLargeSource = $video->thumbnailSource;

        return null;
    }

    /**
     * Get video data pictures.
     *
     * @param array  $data
     * @param string $type
     *
     * @return array
     */
    private function getVideoDataPictures(array $data, string $type = 'thumbnail'): array
    {
        $pictures = [];

        foreach ($data['pictures'] as $picture) {
            if ($picture['type'] === $type) {
                $pictures[] = $picture;
            }
        }

        return $pictures;
    }

    /**
     * @param $uri
     * @param $params
     *
     * @return array
     *
     * @throws \dukt\videos\errors\ApiResponseException
     */
    private function performVideosRequest($uri, $params): array
    {
        $query = $this->queryFromParams($params);

        $data = $this->get($uri, [
            'query' => $query,
        ]);

        $videos = $this->parseVideos($data['data']);

        $more = false;
        $moreToken = null;

        if ($data['paging']['next']) {
            $more = true;
            $moreToken = $query['page'] + 1;
        }

        return [
            'videos' => $videos,
            'moreToken' => $moreToken,
            'more' => $more,
        ];
    }

    /**
     * @param array $params
     *
     * @return array
     */
    private function queryFromParams(array $params = []): array
    {
        $query = [];

        $query['full_response'] = 1;

        if (!empty($params['moreToken'])) {
            $query['page'] = $params['moreToken'];
            unset($params['moreToken']);
        } else {
            $query['page'] = 1;
        }

        // $params['moreToken'] = $query['page'] + 1;

        if (!empty($params['q'])) {
            $query['query'] = $params['q'];
            unset($params['q']);
        }

        $query['per_page'] = $this->getVideosPerPage();

        return array_merge($query, $params);
    }
}
