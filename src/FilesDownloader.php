<?php
declare(strict_types=1);

namespace Ekvio\Integration\Invoker;

use Ekvio\Integration\Contracts\Invoker;
use Ekvio\Integration\Contracts\Profiler;
use League\Flysystem\FilesystemOperator;
use RuntimeException;

class FilesDownloader implements Invoker
{
    protected const NAME = 'Files downloader';

    protected FilesystemOperator $localFs;
    protected FilesystemOperator $remoteFs;
    protected Profiler $profiler;
    protected array $config;

    public function __construct(FilesystemOperator $localFs, FilesystemOperator $remoteFs, Profiler $profiler, array $config = [])
    {
        $this->localFs = $localFs;
        $this->remoteFs = $remoteFs;
        $this->profiler = $profiler;
        $this->config = $config;
    }

    public function __invoke(array $arguments = [])
    {
        $files = $arguments['parameters']['files'] ?? null;
        $destination = $arguments['parameters']['destination'] ?? null;
        $excludeByName = $arguments['parameters']['exclude']['name'] ?? [];
        $excludeByPath = $arguments['parameters']['exclude']['path'] ?? [];

        if(!$files) {
            throw new RuntimeException('No files in parameters');
        }

        if(!$destination) {
            throw new RuntimeException('No found destination directory parameter');
        }

        $this->profiler->profile(sprintf('Checking %s directory existence...', $destination));
        if(!$this->localFs->directoryExists($destination)) {
            $this->profiler->profile(sprintf('Creating %s directory...', $destination));
            $this->localFs->createDirectory($destination, $this->config);
        }

        $fileMap = [];
        foreach ($files as $file) {

            if($excludeByName) {
                $this->profiler->profile('Excluding file by name...');
                $name = basename($file);
                if(in_array($name, $excludeByName, true)) {
                    $this->profiler->profile(sprintf('Excluding %s by name...', $file));
                    continue;
                }
            }

            if($excludeByPath) {
                $this->profiler->profile('Excluding file by path...');
                if(in_array($file, $excludeByPath, true)) {
                    $this->profiler->profile(sprintf('Excluding %s by path...', $file));
                    continue;
                }
            }

            $this->profiler->profile(sprintf('Checking %s file existence...', $file));
            if(!$this->remoteFs->fileExists($file)) {
                throw new RuntimeException(sprintf('Remote file %s not exists', $file));
            }
            $this->profiler->profile(sprintf('Downloading %s...', $file));
            $filename = sprintf('%s/%s', $destination, $file);

            $fileMap[] = [
                'from' => $file,
                'to' => $filename
            ];
        }
        
        $locations = [];
        foreach ($fileMap as $location) {
            $this->profiler->profile(sprintf('Downloading %s...', $location['from']));
            $this->localFs->writeStream($location['to'], $this->remoteFs->readStream($location['from']), $this->config);
            
            $locations[] = $location['to'];
        }

        return $locations;
    }

    public function name(): string
    {
        return self::NAME;
    }
}