<?php
/**
 * @link https://dukt.net/videos/
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\gateways;

use Craft;
use DateTime;
use Dukt\OAuth2\Client\Provider\Vimeo as VimeoProvider;
use dukt\videos\base\Gateway;
use dukt\videos\errors\ApiClientCreateException;
use dukt\videos\errors\ApiResponseException;
use dukt\videos\errors\VideoIdExtractException;
use dukt\videos\errors\VideoNotFoundException;
use dukt\videos\models\Video;
use dukt\videos\models\VideoAuthor;
use dukt\videos\models\VideoExplorer;
use dukt\videos\models\VideoExplorerCollection;
use dukt\videos\models\VideoExplorerSection;
use dukt\videos\models\VideoSize;
use dukt\videos\models\VideoStatistic;
use dukt\videos\models\VideoThumbnail;
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
                'base_uri' => 'https://api.vimeo.com/',
                'headers' => [
                    'Accept' => 'application/vnd.vimeo.*+json;version=3.0',
                    'Authorization' => 'Bearer '.$this->getOauthAccessToken()->getToken(),
                ],
            ];

            return new Client($options);
        } catch (Exception $e) {
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
            $data = $this->fetch('videos/'.$videoId, [
                'query' => [
                    'fields' => 'created_time,description,duration,height,link,name,pictures,pictures,privacy,stats,uri,user,width,download,review_link,files',
                ],
            ]);

            return $this->_parseVideo($data);
        } catch (Exception $e) {
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
     * @since 3.0.0
     */
    public function getExplorer(): VideoExplorer
    {
        $explorer = new VideoExplorer();

        // library section
        $explorer->sections[] = new VideoExplorerSection([
            'name' => Craft::t('videos', 'explorer.section.library.title'),
            'collections' => [
                new VideoExplorerCollection([
                    'name' => Craft::t('videos', 'explorer.collection.upload.title'),
                    'method' => 'uploads',
                    'icon' => 'video-camera',
                ]),
                new VideoExplorerCollection([
                    'name' => Craft::t('videos', 'explorer.collection.favorite.title'),
                    'method' => 'favorites',
                    'icon' => 'thumb-up',
                ]),
            ],
        ]);

        // folders section
        try {
            $foldersData = $this->_fetchFolders();

            if (count($foldersData) > 0) {
                $section = new VideoExplorerSection([
                    'name' => Craft::t('videos', 'explorer.section.folder.title'),
                ]);

                foreach ($foldersData as $folder) {
                    $section->collections[] = new VideoExplorerCollection([
                        'name' => $folder['name'],
                        'method' => 'folder',
                        'options' => ['id' => substr($folder['uri'], strrpos($folder['uri'], '/') + 1)],
                        'icon' => 'folder',
                    ]);
                }

                $explorer->sections[] = $section;
            }
        } catch (ApiResponseException $e) {
            // TODO: log
        }

        // albums section
        try {
            $albumsData = $this->_fetchAlbums();

            if (count($albumsData) > 0) {
                $section = new VideoExplorerSection([
                    'name' => Craft::t('videos', 'explorer.section.album.title'),
                ]);

                foreach ($albumsData as $albumData) {
                    $section->collections[] = new VideoExplorerCollection([
                        'name' => $albumData['name'],
                        'method' => 'album',
                        'icon' => 'layout',
                        'options' => ['id' => substr($albumData['uri'], strpos($albumData['uri'], '/albums/') + strlen('/albums/'))],
                    ]);
                }

                $explorer->sections[] = $section;
            }
        } catch (ApiResponseException $e) {
            // TODO: log
        }

        // channels section
        try {
            $channelsData = $this->_fetchChannels();

            if (count($channelsData) > 0) {
                $section = new VideoExplorerSection([
                    'name' => Craft::t('videos', 'explorer.section.channel.title'),
                ]);

                foreach ($channelsData as $channelData) {
                    $section->collections[] = new VideoExplorerCollection([
                        'name' => $channelData['name'],
                        'method' => 'channel',
                        'options' => ['id' => substr($channelData['uri'], strpos($channelData['uri'], '/channels/') + strlen('/channels/'))],
                    ]);
                }

                $explorer->sections[] = $section;
            }
        } catch (ApiResponseException $e) {
            // TODO: log
        }

        return $explorer;
    }

    /**
     * Returns a list of folder videos.
     *
     * @param array $options
     * @return array
     * @throws ApiResponseException
     *
     * @since 3.0.0
     */
    protected function getVideosFolder(array $options = []): array
    {
        $folderId = $options['id'];
        unset($options['id']);

        return $this->_fetchVideos('me/folders/'.$folderId.'/videos', $options);
    }

    /**
     * Returns a list of videos in an album.
     *
     * @param array $options
     * @return array
     * @throws ApiResponseException
     *
     * @since 2.0.0
     */
    protected function getVideosAlbum(array $options = []): array
    {
        $albumId = $options['id'];
        unset($options['id']);

        return $this->_fetchVideos('me/albums/'.$albumId.'/videos', $options);
    }

    /**
     * Returns a list of videos in a channel.
     *
     * @param array $options
     * @return array
     * @throws ApiResponseException
     *
     * @since 2.0.0
     */
    protected function getVideosChannel(array $options = []): array
    {
        $options['channel_id'] = $options['id'];
        unset($options['id']);

        return $this->_fetchVideos('channels/'.$options['channel_id'].'/videos', $options);
    }

    /**
     * Returns a list of favorite videos.
     *
     * @param array $options
     * @return array
     * @throws ApiResponseException
     *
     * @since 2.0.0
     */
    protected function getVideosFavorites(array $options = []): array
    {
        return $this->_fetchVideos('me/likes', $options);
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
        if (empty($options['q']) === false) {
            $options['query'] = $options['q'];
            unset($options['q']);
        }

        return $this->_fetchVideos('videos', $options);
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
        return $this->_fetchVideos('me/videos', $options);
    }

    /**
     * Sanitize query.
     *
     * @param array $options
     * @return array
     */
    private function _sanitizeQueryPagination(array $options = []): array
    {
        $query = [
            'full_response' => 1,
        ];

        if (empty($options['moreToken']) === false) {
            $query['page'] = $options['moreToken'];
            unset($options['moreToken']);
        } else {
            $query['page'] = 1;
        }

        $query['per_page'] = $this->getVideosPerPage();

        return array_merge($query, $options);
    }

    /**
     * Fetches videos from API.
     *
     * @param string $uri
     * @param array $options
     * @return array
     * @throws ApiResponseException
     */
    private function _fetchVideos(string $uri, array $options = []): array
    {
        $query = $this->_sanitizeQueryPagination($options);

        $data = $this->fetch($uri, [
            'query' => $query,
        ]);

        $videos = $this->_parseVideos($data['data']);

        return [
            'videos' => $videos,
            'pagination' => [
                'moreToken' => empty($data['paging']['next']) === false ? $query['page'] + 1 : null,
                'more' => empty($data['paging']['next']) === false ? true : false,
            ],
        ];
    }

    /**
     * Fetches albums from API.
     *
     * @return array
     * @throws ApiResponseException
     */
    private function _fetchAlbums(): array
    {
        $data = $this->fetch('me/albums');

        return $data['data'];
    }

    /**
     * Fetches channels from API.
     *
     * @return array
     * @throws ApiResponseException
     */
    private function _fetchChannels(): array
    {
        $data = $this->fetch('me/channels');

        return $data['data'];
    }

    /**
     * Fetches folders from API.
     *
     * @return array
     * @throws ApiResponseException
     */
    private function _fetchFolders(): array
    {
        $data = $this->fetch('me/folders');

        return $data['data'];
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
        $video->id = (int)substr($data['uri'], strlen('/videos/'));
        $video->url = 'https://vimeo.com/'.substr($data['uri'], 8);
        $video->title = $data['name'];
        $video->description = $data['description'] ?? '';

        $video->duration = (new DateTime())->modify('+'.(int)$data['duration'].' seconds')->diff(new DateTime());
        $video->publishedAt = new DateTime($data['created_time']);
        $video->gatewayHandle = $this->getHandle();
        $video->raw = $data;

        // author
        $author = new VideoAuthor();
        $author->setVideo($video);
        $author->name = $data['user']['name'];
        $author->url = $data['user']['link'];

        // thumbnail
        $thumbnail = new VideoThumbnail();
        $thumbnail->setVideo($video);
        $this->_populateThumbnail($thumbnail, $data);

        // size
        $size = new VideoSize();
        $size->setVideo($video);
        $size->width = (int)$data['width'];
        $size->height = (int)$data['height'];

        // statistic
        $statistic = new VideoStatistic();
        $statistic->setVideo($video);
        $statistic->playCount = $data['stats']['plays'] ?? 0;

        // privacy
        if (in_array($data['privacy']['view'], ['nobody', 'contacts', 'password', 'users', 'disable'], true) === true) {
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
        if (is_array($data['pictures']) === false) {
            return;
        }

        // need to find all thumbnails
        $thumbnails = [];
        foreach ($data['pictures'] as $picture) {
            if ($picture['type'] === 'thumbnail') {
                $thumbnails[] = $picture;
            }
        }

        $smallestSize = 0;
        $largestSize = 0;

        foreach ($thumbnails as $thumbnailData) {
            if ($smallestSize === 0 || (int)$thumbnailData['width'] < $smallestSize) {
                if ((int)$thumbnailData['width'] >= 300) {
                    $smallestSize = (int)$thumbnailData['width'];
                    $thumbnail->smallestSourceUrl = $thumbnailData['link'];
                }
            }

            if ((int)$thumbnailData['width'] > $largestSize) {
                $largestSize = (int)$thumbnailData['width'];
                $thumbnail->largestSourceUrl = $thumbnailData['link'];
            }
        }
    }
}
