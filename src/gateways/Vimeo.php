<?php
/**
 * @link https://dukt.net/videos/
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\gateways;

use DateTime;
use Dukt\OAuth2\Client\Provider\Vimeo as VimeoProvider;
use dukt\videos\base\Gateway;
use dukt\videos\errors\ApiClientCreateException;
use dukt\videos\errors\CollectionParsingException;
use dukt\videos\errors\VideoIdExtractException;
use dukt\videos\errors\VideoNotFoundException;
use dukt\videos\models\Collection;
use dukt\videos\models\Section;
use dukt\videos\models\Video;
use dukt\videos\models\VideoAuthor;
use dukt\videos\models\VideoSize;
use dukt\videos\models\VideoStatistic;
use Exception;
use GuzzleHttp\Client;
use League\OAuth2\Client\Provider\AbstractProvider;

/**
 * Vimeo gateway.
 *
 * @author Dukt <support@dukt.net>
 * @since 2.0.0
 */
class Vimeo extends Gateway
{
    /**
     * {@inheritdoc}
     *
     * @since 2.0.0
     */
    public function getName(): string
    {
        return 'Vimeo';
    }

    /**
     * {@inheritdoc}
     *
     * @since 2.0.0
     */
    public function getIconAlias(): string
    {
        return '@dukt/videos/icons/vimeo.svg';
    }

    /**
     * {@inheritdoc}
     *
     * @since 2.0.0
     */
    public function createOauthProvider(array $options): AbstractProvider
    {
        return new VimeoProvider($options);
    }

    /**
     * {@inheritdoc}
     *
     * @since 2.0.0
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
     *
     * @since 2.0.0
     */
    public function getOauthProviderApiConsoleUrl(): string
    {
        return 'https://developer.vimeo.com/apps';
    }

    /**
     * {@inheritdoc}
     *
     * @since 3.0.0
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
     *
     * @since 3.0.0
     */
    public function fetchVideoById(string $videoId): Video
    {
        try {
            $data = $this->fetch('videos/'.$videoId, [
                'query' => [
                    'fields' => 'created_time,description,duration,height,link,name,pictures,pictures,privacy,stats,uri,user,width,download,review_link,files',
                ],
            ]);

            return $this->_parseVideo($data);
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
        return 'https://player.vimeo.com/video/%s';
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
     * @return array
     * @throws CollectionParsingException
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
     *
     * @since 3.0.0
     */
    protected function createApiClient(): Client
    {
        try {
            $options = [
                'base_uri' => 'https://api.vimeo.com/',
                'headers' => [
                    'Accept' => 'application/vnd.vimeo.*+json;version=3.0',
                    'Authorization' => 'Bearer '.$this->getOauthAccessToken()->getToken(),
                ],
            ];

            return new Client($options);
        } catch (Exception $e) {
            throw new ApiClientCreateException(/* TODO: more precise message */);
        }
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
        $data = $this->fetch('me/albums', [
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
        $data = $this->fetch('me/channels', [
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

        $data = $this->fetch($uri, [
            'query' => $query,
        ]);

        $videos = $this->_parseVideos($data['data']);

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
        $video->id = (int)substr($data['uri'], strlen('/videos/'));
        $video->url = 'https://vimeo.com/'.substr($data['uri'], 8);
        $video->title = $data['name'];
        $video->description = $data['description'];
        $video->duration = (new DateTime())->modify('+'.(int)$data['duration'].' seconds')->diff(new DateTime());
        $video->publishedAt = new DateTime($data['created_time']);
        $video->thumbnailSourceUrl = $this->_getThumbnailSource($data);
        $video->gatewayHandle = $this->getHandle();
        $video->raw = $data;

        // author
        $author = new VideoAuthor();
        $author->name = $data['user']['name'];
        $author->url = $data['user']['link'];
        $video->author = $author;

        // size
        $size = new VideoSize();
        $size->width = (int)$data['width'];
        $size->height = (int)$data['height'];
        $video->size = $size;

        // statistic
        $statistic = new VideoStatistic();
        $statistic->playCount = $data['stats']['plays'] ?? 0;
        $video->statistic = $statistic;

        // privacy
        if (in_array($data['privacy']['view'], ['nobody', 'contacts', 'password', 'users', 'disable'], true) === true) {
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
        if (is_array($data['pictures']) === false) {
            return null;
        }

        // need to find all thumbnails
        $thumbnails = [];
        foreach ($data['pictures'] as $picture) {
            if ($picture['type'] === 'thumbnail') {
                $thumbnails[] = $picture;
            }
        }

        // need to find the largest thumbnail
        $largestSize = 0;
        $largestThumbnailSource = null;
        foreach ($thumbnails as $thumbnail) {
            if ((int)$thumbnail['width'] > $largestSize) {
                $largestThumbnailSource = $picture['link'];
                $largestSize = (int)$thumbnail['width'];
            }
        }

        return $largestThumbnailSource;
    }
}
