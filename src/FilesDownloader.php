<?php
declare(strict_types=1);

namespace App;

use Ekvio\Integration\Contracts\Invoker;
use Ekvio\Integration\Contracts\Profiler;
use League\Flysystem\FilesystemInterface;
use RuntimeException;

/**
 * Class FilesDownloader
 * @package App
 */
class FilesDownloader implements Invoker
{
    private const NAME = 'Files downloader';

    /**
     * @var FilesystemInterface
     */
    private $localFs;
    /**
     * @var FilesystemInterface
     */
    private $remoteFs;
    /**
     * @var Profiler
     */
    private $profiler;

    public function __construct(FilesystemInterface $localFs, FilesystemInterface $remoteFs, Profiler $profiler)
    {
        $this->localFs = $localFs;
        $this->remoteFs = $remoteFs;
        $this->profiler = $profiler;
    }

    /**
     * @param array $parameters
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function __invoke(array $parameters = []): void
    {
        $files = $parameters['files'];
        $destination = $parameters['destination'];

        if(!$files) {
            throw new RuntimeException('No files in parameters');
        }

        if(!$destination) {
            throw new RuntimeException('No found destination directory parameter');
        }

        $this->profiler->profile(sprintf('Checking %s directory existence...', $destination));
        if(!$this->localFs->has($destination)) {
            $this->profiler->profile(sprintf('Creating %s directory....', $destination));
            $this->localFs->createDir($destination);
        }

        foreach ($files as $file) {
            $this->profiler->profile(sprintf('Checking %s file existence...', $file));
            if(!$this->remoteFs->has($file)) {
                throw new RuntimeException(sprintf('Remote file %s not exists', $file));
            }
            $this->profiler->profile(sprintf('Downloading %s...', $file));
            $filename = sprintf('%s/%s', $destination, $file);

            if(!$this->localFs->putStream($filename, $this->remoteFs->readStream($file))) {
                throw new RuntimeException(sprintf('Cannot write to %s file...', $filename));
            }
        }
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return self::NAME;
    }
}