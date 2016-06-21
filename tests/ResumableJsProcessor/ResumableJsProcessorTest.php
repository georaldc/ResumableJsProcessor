<?php

namespace ResumableJsProcessor\Tests;

use ResumableJsProcessor\ResumableJsProcessor;

class ResumableJsProcessorTest extends \PHPUnit_Framework_TestCase
{
    protected $resumable;

    protected function setUp()
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

    public function testProcessUpload()
    {
        $this->generateUploads();
        $this->resumable->setChunkPath('tests/chunks');
        $fileCounter = 1;
        $fileUploaded = null;
        if (!is_dir($this->resumable->getUploadPath())) {
            mkdir($this->resumable->getUploadPath(), 777);
        }
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
                    'tmp_name' => 'tests/temp/test' . $fileCounter,
                    'error' => 0,
                    'size' => 6,
                ],
            ];
            if ($fileUploaded = $this->resumable->process(true)) {
                break;
            }
            $fileCounter++;
        }
        $this->assertEquals('tests/uploads' . DIRECTORY_SEPARATOR . $_POST['resumableFilename'], $fileUploaded);
        $this->assertFileExists('tests/uploads' . DIRECTORY_SEPARATOR . $_POST['resumableFilename']);
    }

    /**
     * @codeCoverageIgnore
     */
    private function generateUploads()
    {
        $tempDirectory = 'tests/temp';
        $i = 0;
        $string = 'foobar';
        if (!is_dir($tempDirectory)) {
            mkdir($tempDirectory, 777);
        }
        while ($i < strlen($string)) {
            if (($fp = fopen($tempDirectory . DIRECTORY_SEPARATOR . 'test' . ($i + 1), 'w')) !== false) {
                fwrite($fp, $string[$i]);
                $i++;
            }
        }
    }
}
