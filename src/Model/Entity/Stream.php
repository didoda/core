<?php
/**
 * BEdita, API-first content management framework
 * Copyright 2017 ChannelWeb Srl, Chialab Srl
 *
 * This file is part of BEdita: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * See LICENSE.LGPL or <http://gnu.org/licenses/lgpl-3.0.html> for more details.
 */

namespace BEdita\Core\Model\Entity;

use BEdita\Core\Filesystem\FilesystemRegistry;
use BEdita\Core\Utility\JsonApiSerializable;
use Cake\Event\EventDispatcherTrait;
use Cake\Log\LogTrait;
use Cake\ORM\Entity;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use Laminas\Diactoros\Stream as LaminasStream;
use League\Flysystem\FileNotFoundException;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LogLevel;

/**
 * Stream Entity
 *
 * @property string $uuid
 * @property int $version
 * @property int|null $object_id
 * @property string $uri
 * @property string|null $file_name
 * @property string $mime_type
 * @property int $file_size
 * @property string $hash_md5
 * @property string $hash_sha1
 * @property int $width
 * @property int $height
 * @property int $duration
 * @property array $file_metadata
 * @property bool $private_url
 * @property \Psr\Http\Message\StreamInterface|null $contents
 * @property string|null $url
 * @property \Cake\I18n\Time $created
 * @property \Cake\I18n\Time $modified
 *
 * @property \BEdita\Core\Model\Entity\ObjectEntity|null $object
 */
class Stream extends Entity implements JsonApiSerializable
{
    use EventDispatcherTrait;
    use JsonApiTrait;
    use LogTrait;

    public const FILE_PROPERTIES = [
        'file_name',
        'mime_type',
        'file_size',
        'hash_md5',
        'hash_sha1',
        'width',
        'height',
        'duration',
        'file_metadata',
        'private_url',
    ];

    /**
     * @inheritDoc
     */
    protected $_accessible = [
        '*' => false,
        'file_name' => true,
        'mime_type' => true,
        'contents' => true,
    ];

    /**
     * @inheritDoc
     */
    protected $_hidden = [
        'object_id',
        'uri',
    ];

    /**
     * @inheritDoc
     */
    protected $_virtual = [
        'url',
    ];

    /**
     * Mime types typically containing EXIF headers.
     * Used to reduce possible errors in `exif_read_data()`
     *
     * @var array
     */
    public const EXIF_MIME_TYPES = ['image/jpeg', 'image/jpg', 'image/tiff'];

    /**
     * Exif sections to extract.
     *
     * @var array
     */
    public const EXIF_SECTIONS = ['FILE', 'COMPUTED', 'IFD0', 'THUMBNAIL', 'COMMENT', 'EXIF'];

    /**
     * Get filesystem path (including mount point) under which file should be stored.
     *
     * Result of this method will be generally used as the entity `uri` on save.
     *
     * @param string $filesystem Filesystem for which path must be generated.
     * @param int $subLevels Number of sub-levels to organize files.
     * @return string
     */
    public function filesystemPath($filesystem = 'default', $subLevels = 0)
    {
        if (!$this->has('uuid')) {
            // Generate random UUID. This is needed for path, otherwise we'd let Cake take care of it.
            $this->uuid = Text::uuid();
        }

        // Prepare file name.
        $fileName = $extension = '';
        if ($this->has('file_name')) {
            $fileName = Text::transliterate($this->file_name);
            preg_match('/^(.+?)((?:\.[a-z0-9]+)*)$/i', strtolower(basename($fileName)), $matches);
            [, $fileName, $extension] = $matches + [null, '', ''];
            $fileName = '-' . Text::slug($fileName);
        }
        $fileName = $this->uuid . $fileName . $extension;

        // Prepare sub-levels.
        $prefix = '';
        $hash = sha1($fileName);
        $subLevels = floor(max(0, min($subLevels, strlen($hash) / 2)));
        for ($i = 0; $i < $subLevels; $i++) {
            $prefix .= substr($hash, $i * 2, 2) . '/';
        }

        return sprintf(
            '%s://%s%s',
            $filesystem, // Flysystem mount point.
            $prefix, // Prefix.
            $fileName // File name.
        );
    }

    /**
     * Magic getter for file contents.
     *
     * It downloads the file using Flysystem adapter and returns a PSR-7 stream.
     *
     * Accessing file contents should be used with care, since it might incur in high network traffic
     * if files are stored in an external location such as another server or a service such as Amazon S3.
     *
     * @return \Psr\Http\Message\StreamInterface|null
     */
    protected function _getContents()
    {
        if (!empty($this->_properties['contents'])) {
            // Downloaded already.
            return $this->_properties['contents'];
        }

        if (!$this->has('uri')) {
            // This stream has no contents yet.
            return null;
        }

        try {
            $readStream = FilesystemRegistry::getMountManager()->readStream($this->uri);
        } catch (FileNotFoundException $e) {
            // Unable to read from filesystem. Better log a warning...
            $this->log(sprintf('Unable to read file contents: %s', $this->uri), 'warning');

            return null;
        }

        $stream = new LaminasStream($readStream, 'r');

        return $this->_properties['contents'] = $stream;
    }

    /**
     * Create a Laminas Stream out of a PHP resource.
     *
     * In the meanwhile, file size, MD5 and SHA1 hashes are
     *
     * @param resource $source Original resource.
     * @return \Laminas\Diactoros\Stream
     * @throws \InvalidArgumentException Throws an exception if the parameter is not a resource.
     */
    protected function createStream($source)
    {
        $info = stream_get_meta_data($source);
        if ($info['seekable'] === true) {
            rewind($source);
        }

        $resource = fopen('php://temp', 'wb+');
        stream_copy_to_stream($source, $resource);
        rewind($resource);

        // File size.
        $stat = fstat($resource);
        $this->file_size = $stat['size'];
        $this->setDirty('file_size', true);

        // MD5.
        $hashContext = hash_init('md5');
        rewind($resource);
        hash_update_stream($hashContext, $resource);
        $this->hash_md5 = hash_final($hashContext);
        $this->setDirty('hash_md5', true);

        // SHA1.
        $hashContext = hash_init('sha1');
        rewind($resource);
        hash_update_stream($hashContext, $resource);
        $this->hash_sha1 = hash_final($hashContext);
        $this->setDirty('hash_sha1', true);

        // Read additional metadata (only images for now)
        $this->readFileMetadata($resource);
        rewind($resource);
        $this->dispatchEvent('Stream.create', [$resource]);

        // Stream.
        rewind($resource);
        $stream = new LaminasStream($resource, 'r');

        return $stream;
    }

    /**
     * Setter for file contents.
     *
     * @param mixed $contents File contents. Can be either a PSR-7 stream, a PHP stream resource, or any
     *      other value that can be cast to string (scalars, nulls, and objects implementing `__toString()` method).
     * @return \Psr\Http\Message\StreamInterface
     * @throws \InvalidArgumentException Throws an exception if contents could not be converted to a PSR-7 stream.
     */
    protected function _setContents($contents)
    {
        if ($contents instanceof StreamInterface) {
            // Already a PSR-7 stream.
            return $this->createStream($contents->detach());
        }
        if (is_resource($contents)) {
            // Not a stream, but a resource that can hopefully be attached to a PSR-7 stream.
            return $this->createStream($contents);
        }
        if (is_scalar($contents) || is_null($contents) || (is_object($contents) && method_exists($contents, '__toString'))) {
            // A value that can be cast to a string. A new PSR-7 stream is created, and value is written to it.
            $resource = fopen('php://temp', 'wb+');
            fwrite($resource, (string)$contents);

            return $this->createStream($resource);
        }

        throw new \InvalidArgumentException(
            'Invalid contents provided, must be a PSR-7 stream, a resource or a value that can be converted to string'
        );
    }

    /**
     * Getter for public URL from which file can be accessed.
     *
     * @return string|null
     */
    protected function _getUrl()
    {
        if (!empty($this->_properties['url'])) {
            // Already computed the public URL. Let's avoid requesting it again.
            return $this->_properties['url'];
        }

        if (!$this->has('uri') || $this->get('private_url')) {
            // Stream has a private UR or is not yet (or not any more) associated to any object.
            return null;
        }

        return $this->_properties['url'] = FilesystemRegistry::getPublicUrl($this->uri);
    }

    /**
     * Read additional metadata from stream (only images for now)
     *
     * @param resource $resource Resource streammed
     * @return void
     */
    protected function readFileMetadata($resource): void
    {
        if (!preg_match('/image\//', $this->mime_type)) {
            return;
        }

        // Use getimagesizefromstring for image formats not supported by exif_read_data
        if (!in_array($this->mime_type, static::EXIF_MIME_TYPES)) {
            if (!function_exists('getimagesizefromstring')) {
                return;
            }

            rewind($resource);
            $content = stream_get_contents($resource);
            if (!empty($content)) {
                $size = getimagesizefromstring($content);
                if (!empty($size)) {
                    $this->width = $size[0];
                    $this->height = $size[1];
                }
            }

            return;
        }

        if (!function_exists('exif_read_data')) {
            return;
        }

        rewind($resource);
        // Set custom error handler to catch errors that still use "traditional" PHP error reporting.
        // exif_read_data() is one such function, evading usual try-catch blocks.
        set_error_handler(
            function (int $code, string $message, string $filename, int $lineNumber): void {
                throw new \ErrorException($message, $code, LOG_ERR, $filename, $lineNumber);
            }
        );

        try {
            $exif = exif_read_data($resource, null, true);
        } catch (\ErrorException $e) {
            // Log a warning if reading EXIF throws an error, but keep going
            // so that other metadata is eventually updated
            $this->log(sprintf('Error reading EXIF headers for stream %s (object ID: %d)', $this->uuid, $this->object_id), LogLevel::WARNING);
            $exif = false;
        } finally {
            // Restore previous error handler so that errors/exceptions are handled as before
            restore_error_handler();
        }

        if ($exif === false) {
            return;
        }

        // Extract image width/height
        $this->width = Hash::get($exif, 'COMPUTED.Width', null);
        $this->height = Hash::get($exif, 'COMPUTED.Height', null);

        // Filter non-standard sections
        $exif = array_intersect_key($exif, array_flip(static::EXIF_SECTIONS));
        // Filter undefined tags, which may break JSON encoding
        $exif = array_map(function (array $section): array {
            return array_filter($section, function (string $key): bool {
                return strpos($key, 'UndefinedTag:') === false;
            }, ARRAY_FILTER_USE_KEY);
        }, $exif);
        // Check if data is JSON-encodable
        if (json_encode($exif) === false) {
            return;
        }

        $this->file_metadata = $exif;
    }
}
