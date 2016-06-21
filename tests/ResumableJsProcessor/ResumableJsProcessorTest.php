<?php

namespace ResumableJsProcessor\Tests;

use ResumableJsProcessor\ResumableJsProcessor;

class ResumableJsProcessorTest extends \PHPUnit_Framework_TestCase
{
    protected $resumable;

    public function setUp()
    {
        $this->resumable = new ResumableJsProcessor('tests/uploads');
    }

    public function testGetUploadPath()
    {
        $this->assertEquals('tests/uploads', $this->resumable->getUploadPath());
    }

    public function testSetNewUploadPath()
    {
        $this->resumable->setUploadPath('new/path/to/uploads');
        $this->assertEquals('new/path/to/uploads', $this->resumable->getUploadPath());
    }

    public function testGetChunkPath()
    {
        $this->assertEquals(sys_get_temp_dir(), $this->resumable->getChunkPath());
    }

    public function testSetNewChunkPath()
    {
        $this->resumable->setChunkPath('new/path/to/chunk/files');
        $this->assertEquals('new/path/to/chunk/files', $this->resumable->getChunkPath());
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
}
