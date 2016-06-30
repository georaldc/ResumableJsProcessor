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

    public function testGetDefaultChunkPath()
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
        $this->resumable->setMode(ResumableJsProcessor::MODE_TEST_CHUNK);
        $this->assertEquals(ResumableJsProcessor::MODE_TEST_CHUNK, $this->resumable->getMode());
        $this->resumable->setMode(ResumableJsProcessor::MODE_UPLOAD_CHUNK);
        $this->assertEquals(ResumableJsProcessor::MODE_UPLOAD_CHUNK, $this->resumable->getMode());
    }

    public function testSetResumableParametersContainer()
    {
        $resumableParametersContainer = $this->getMockBuilder('ResumableJsProcessor\ResumableParametersContainer')
            ->setConstructorArgs([ResumableJsProcessor::MODE_TEST_CHUNK])
            ->getMock();
        $resumableParametersContainer
            ->method('getParameters')
            ->willReturn([
                'resumableIdentifier' => 'foobar',
                'resumableFilename' => 'testfile.foobar',
                'resumableChunkNumber' => '1',
                'resumableTotalSize' => 100,
                'resumableTotalChunks' => 40,
            ]);
        $this->resumable->setResumableParametersContainer($resumableParametersContainer);
        $this->assertEquals([
            'resumableIdentifier' => 'foobar',
            'resumableFilename' => 'testfile.foobar',
            'resumableChunkNumber' => '1',
            'resumableTotalSize' => 100,
            'resumableTotalChunks' => 40,
        ], $this->resumable->getResumableParameters());
    }

    public function testProcessUpload()
    {
        $this->generateUploads();
        $fileCounter = 1;
        $fileUploaded = null;
        while ($fileCounter <= 6) {
            // Setup environment for ResumableParametersContainer
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
            $resumable = new ResumableJsProcessor(vfsStream::url('tests/uploads'));
            $resumable->setChunkPath(vfsStream::url('tests/chunks'));
            $resumable->setMode(ResumableJsProcessor::MODE_UPLOAD_CHUNK);
            if ($fileUploaded = $resumable->process(true)) {
                $this->assertFileNotExists($resumable->getChunkPath() . DIRECTORY_SEPARATOR . '6-testtxt');
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
