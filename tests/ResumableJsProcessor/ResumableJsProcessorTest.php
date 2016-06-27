<?php

namespace ResumableJsProcessor\Tests;

use org\bovigo\vfs\vfsStream;
use ResumableJsProcessor\ResumableJsProcessor;

class ResumableJsProcessorTest extends \PHPUnit_Framework_TestCase
{
    protected $resumable;
    protected $root;

    protected function setUp()
    {
        $this->root = vfsStream::setup('tests');
        $this->resumable = new ResumableJsProcessor(vfsStream::url('tests/uploads'));
    }

    public function testGetUploadPath()
    {
        $this->assertEquals('vfs://tests/uploads', $this->resumable->getUploadPath());
    }

    public function testSetNewUploadPath()
    {
        $this->resumable->setUploadPath(vfsStream::url('tests/new-uploads-path'));
        $this->assertEquals('vfs://tests/new-uploads-path', $this->resumable->getUploadPath());
        $this->assertTrue(is_dir($this->resumable->getUploadPath()));
    }

    public function testGetChunkPath()
    {
        $this->assertEquals(sys_get_temp_dir(), $this->resumable->getChunkPath());
    }

    public function testSetNewChunkPath()
    {
        $this->resumable->setChunkPath(vfsStream::url('tests/new-chunks-path'));
        $this->assertEquals('vfs://tests/new-chunks-path', $this->resumable->getChunkPath());
        $this->assertTrue(is_dir($this->resumable->getUploadPath()));
    }

    public function testGetResumableMode()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->assertEquals(ResumableJsProcessor::MODE_TEST_CHUNK, $this->resumable->getMode());
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_FILES = [
            'file' => [
                'name' => 'blob',
                'type' => 'application/octet-stream',
                'tmp_name' => '/tmp/phpFile',
                'error' => 0,
                'size' => 1024,
            ],
        ];
        $this->assertEquals(ResumableJsProcessor::MODE_UPLOAD_CHUNK, $this->resumable->getMode());
    }

    public function testGetResumableParameters()
    {
        $this->assertEquals([
            'resumableIdentifier' => '',
            'resumableFilename' => '',
            'resumableChunkNumber' => '',
            'resumableTotalSize' => 0,
        ], $this->resumable->getResumableParameters());
    }

    public function testProcessUpload()
    {
        $this->generateUploads();
        $this->resumable->setChunkPath(vfsStream::url('tests/chunks'));
        $fileCounter = 1;
        $fileUploaded = null;
        while ($fileCounter <= 6) {
            $_POST = [
                'resumableChunkNumber' => $fileCounter,
                'resumableFilename' => 'test.txt',
                'resumableTotalChunks' => 6,
                'resumableIdentifier' => '6-testtxt',
                'resumableTotalSize' => 6,
            ];
            $_FILES = [
                'file' => [
                    'name' => 'blob',
                    'type' => 'application/octet-stream',
                    'tmp_name' => vfsStream::url('tests/temp') . '/test' . $fileCounter,
                    'error' => 0,
                    'size' => 6,
                ],
            ];
            if ($fileUploaded = $this->resumable->process(true)) {
                break;
            }
            $fileCounter++;
        }
        $this->assertEquals('vfs://tests/uploads' . DIRECTORY_SEPARATOR . $_POST['resumableFilename'], $fileUploaded);
        $this->assertFileExists('vfs://tests/uploads' . DIRECTORY_SEPARATOR . $_POST['resumableFilename']);
    }

    /**
     * @codeCoverageIgnore
     */
    private function generateUploads()
    {
        $i = 0;
        $string = 'foobar';
        $temp = vfsStream::newDirectory('temp')->at($this->root);
        while ($i < strlen($string)) {
            vfsStream::newFile('test' . ($i + 1))->at($temp)->setContent($string[$i]);
            $i++;
        }
    }
}
