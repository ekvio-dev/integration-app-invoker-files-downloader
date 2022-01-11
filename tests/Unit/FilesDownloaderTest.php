<?php
declare(strict_types=1);

namespace Tests\Unit;

use Ekvio\Integration\Invoker\FilesDownloader;
use League\Flysystem\Filesystem;
use League\Flysystem\Memory\MemoryAdapter;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\DummyProfiler;

class FilesDownloaderTest extends TestCase
{
    protected $localFs;
    protected $remoteFs;
    protected $profiler;

    protected function setUp(): void
    {
        $this->localFs = new Filesystem(new MemoryAdapter());
        $this->remoteFs = new Filesystem(new MemoryAdapter());
        $this->profiler = new DummyProfiler();
    }

    public function testArgumentFilesNotExist()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No files in parameters');

        $downloader = new FilesDownloader($this->localFs, $this->remoteFs, $this->profiler);
        $downloader(['parameters' => []]);
    }

    public function testDirectoryNotExist()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No found destination directory parameter');

        $downloader = new FilesDownloader($this->localFs, $this->remoteFs, $this->profiler);
        $downloader(['parameters' => [
            'files' => '/tmp/test.txt'
        ]]);
    }

    public function testRemoteFilesNotExists()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Remote file /tmp/test.txt not exists');

        $downloader = new FilesDownloader($this->localFs, $this->remoteFs, $this->profiler);
        $downloader(['parameters' => [
            'files' => ['/tmp/test.txt'],
            'destination' => 'temp'
        ]]);
    }

    public function testDownloadRemoteFileSuccess()
    {
        $this->remoteFs->put('/tmp/test.txt', 'some test content.');

        $downloader = new FilesDownloader($this->localFs, $this->remoteFs, $this->profiler);
        $downloader(['parameters' => [
            'files' => ['/tmp/test.txt'],
            'destination' => 'temp'
        ]]);

        $this->assertTrue($this->localFs->has('temp/tmp/test.txt'));
    }

    public function testExcludeByName()
    {
        $this->remoteFs->put('tmp/test.txt', 'test 1');
        $this->remoteFs->put('tmp/test2.txt', 'test 2');
        $this->remoteFs->put('tmp2/test.txt', 'test 3');

        $downloader = new FilesDownloader($this->localFs, $this->remoteFs, $this->profiler);
        $downloader(['parameters' => [
            'files' => [
                'tmp/test.txt',
                'tmp/test2.txt',
                'tmp2/test.txt'
            ],
            'destination' => 'temp',
            'exclude' => ['name' => ['test.txt']]
        ]]);

        $this->assertFalse($this->localFs->has('temp/tmp/test.txt'));
        $this->assertFalse($this->localFs->has('temp/tmp2/test.txt'));
        $this->assertTrue($this->localFs->has('temp/tmp/test2.txt'));
    }

    public function testExcludeByPath()
    {
        $this->remoteFs->put('tmp/test.txt', 'test 1');
        $this->remoteFs->put('tmp/test2.txt', 'test 2');
        $this->remoteFs->put('tmp2/test.txt', 'test 3');

        $downloader = new FilesDownloader($this->localFs, $this->remoteFs, $this->profiler);
        $downloader(['parameters' => [
            'files' => [
                'tmp/test.txt',
                'tmp/test2.txt',
                'tmp2/test.txt'
            ],
            'destination' => 'temp',
            'exclude' => ['path' => ['tmp/test.txt', 'tmp/test2.txt']]
        ]]);

        $this->assertFalse($this->localFs->has('temp/tmp/test.txt'));
        $this->assertFalse($this->localFs->has('temp/tmp/test2.txt'));
        $this->assertTrue($this->localFs->has('temp/tmp2/test.txt'));
    }
}