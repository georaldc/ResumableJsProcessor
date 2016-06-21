<?php

namespace ResumableJsProcessor;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FilesystemIterator;
use SebastianBergmann\GlobalState\RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ResumableJsProcessor
{
    const MODE_TEST_CHUNK = 1;
    const MODE_UPLOAD_CHUNK = 2;

    protected $uploadPath;
    protected $chunkPath;

    public function __construct($uploadPath)
    {
        $this->setUploadPath($uploadPath);
    }

    public function setUploadPath($uploadPath)
    {
        $this->uploadPath = $uploadPath;
    }

    public function getUploadPath()
    {
        return $this->uploadPath;
    }

    public function setChunkPath($chunkPath)
    {
        $this->chunkPath = $chunkPath;
    }

    public function getChunkPath()
    {
        if (!$this->chunkPath) {
            return sys_get_temp_dir();
        } else {
            return $this->chunkPath;
        }
    }

    public function getMode()
    {
        $mode = self::MODE_TEST_CHUNK;
        if (!empty($_FILES)) {
            $mode = self::MODE_UPLOAD_CHUNK;
        }
        return $mode;
    }

    public function getResumableParameters()
    {
        $mode = $this->getMode();
        $parameters = [
            'resumableIdentifier' => '',
            'resumableFilename' => '',
            'resumableChunkNumber' => '',
            'resumableTotalSize' => 0,
        ];
        if (self::MODE_TEST_CHUNK === $mode) {
            if (isset($_GET['resumableIdentifier']) && trim($_GET['resumableIdentifier']) != '') {
                $parameters['resumableIdentifier'] = trim($_GET['resumableIdentifier']);
            }
            if (isset($_GET['resumableFilename']) && trim($_GET['resumableFilename']) != '') {
                $parameters['resumableFilename'] = trim($_GET['resumableFilename']);
            }
            if (isset($_GET['resumableChunkNumber']) && trim($_GET['resumableChunkNumber']) != '') {
                $parameters['resumableChunkNumber'] = (int) $_GET['resumableChunkNumber'];
            }
            if (isset($_GET['resumableTotalSize']) && trim($_GET['resumableTotalSize']) != '') {
                $parameters['resumableTotalSize'] = (int) $_GET['resumableTotalSize'];
            }
            if (isset($_GET['resumableTotalChunks']) && trim($_GET['resumableTotalChunks']) != '') {
                $parameters['resumableTotalChunks'] = (int) $_GET['resumableTotalChunks'];
            }
        } elseif (self::MODE_UPLOAD_CHUNK === $mode) {
            if (isset($_POST['resumableIdentifier']) && trim($_POST['resumableIdentifier']) != '') {
                $parameters['resumableIdentifier'] = trim($_POST['resumableIdentifier']);
            }
            if (isset($_POST['resumableFilename']) && trim($_POST['resumableFilename']) != '') {
                $parameters['resumableFilename'] = trim($_POST['resumableFilename']);
            }
            if (isset($_POST['resumableChunkNumber']) && trim($_POST['resumableChunkNumber']) != '') {
                $parameters['resumableChunkNumber'] = (int) $_POST['resumableChunkNumber'];
            }
            if (isset($_POST['resumableTotalSize']) && trim($_POST['resumableTotalSize']) != '') {
                $parameters['resumableTotalSize'] = (int) $_POST['resumableTotalSize'];
            }
            if (isset($_POST['resumableTotalChunks']) && trim($_POST['resumableTotalChunks']) != '') {
                $parameters['resumableTotalChunks'] = (int) $_POST['resumableTotalChunks'];
            }
        }
        return $parameters;
    }

    public function process($testMode = false)
    {
        $mode = $this->getMode();
        $parameters = $this->getResumableParameters();
        $chunkPath = $this->getChunkPath();
        $chunkPath .= DIRECTORY_SEPARATOR . $parameters['resumableIdentifier'];
        $chunkFile = $chunkPath . DIRECTORY_SEPARATOR . $parameters['resumableFilename'] . '.part' . $parameters['resumableChunkNumber'];
        if (self::MODE_TEST_CHUNK === $mode) {
            if (file_exists($chunkFile)) {
                header('HTTP/1.0 200 Ok');
            } else {
                header('HTTP/1.0 204 No Content');
            }
        } elseif (self::MODE_UPLOAD_CHUNK === $mode) {
            foreach ($_FILES as $file) {
                $uploadedFile = new UploadedFile($file['tmp_name'], $file['name'], $file['type'], $file['size'], $file['error'], (true === $testMode));
                if (UPLOAD_ERR_OK !== $uploadedFile->getError()) {
                    continue;
                }
                // create our chunk directory
                if (!is_dir($chunkPath)) {
                    if (true !== @mkdir($chunkPath, 0777, true)) {
                        if (!is_dir($chunkPath)) {
                            throw new \RuntimeException('An error has occurred trying to create the chunk directory ' . $chunkPath);
                        }
                    }
                }
                // move the uploaded file
                $uploadedFile->move($chunkPath, $parameters['resumableFilename'] . '.part' . $parameters['resumableChunkNumber']);
            }
        }
        if ($this->validateChunks()) {
            return $this->createFileFromChunks();
        }
        return false;
    }

    protected function validateChunks()
    {
        $parameters = $this->getResumableParameters();
        $chunkPath = $this->getChunkPath();
        $chunkPath .= DIRECTORY_SEPARATOR . $parameters['resumableIdentifier'];
        if (!is_dir($chunkPath)) {
            return false;
        }
        $totalChunkedSize = 0;
        foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(realpath($chunkPath), FilesystemIterator::SKIP_DOTS)) as $file){
            $totalChunkedSize += $file->getSize();
        }
        return $totalChunkedSize >= $parameters['resumableTotalSize'];
    }

    protected function createFileFromChunks()
    {
        $parameters = $this->getResumableParameters();
        $chunkPath = $this->getChunkPath();
        $chunkPath .= DIRECTORY_SEPARATOR . $parameters['resumableIdentifier'];
        if (!is_dir($this->getUploadPath())) {
            throw new RuntimeException('Upload path does not exist: ' . $this->getUploadPath());
        }
        if (($fp = fopen($this->getUploadPath() . DIRECTORY_SEPARATOR . $parameters['resumableFilename'], 'w')) !== false) {
            for ($i = 1; $i <= $parameters['resumableTotalChunks']; $i++) {
                $chunkFile = $chunkPath . DIRECTORY_SEPARATOR . $parameters['resumableFilename'] . '.part' . $i;
                fwrite($fp, file_get_contents($chunkFile));
            }
            fclose($fp);
        } else {
            return false;
        }
        if (is_dir($chunkPath . '_UNUSED')) {
            $this->recursiveDeleteChunkDirectory($chunkPath . '_UNUSED');
        }
        if (rename($chunkPath, $chunkPath . '_UNUSED')) {
            $this->recursiveDeleteChunkDirectory($chunkPath . '_UNUSED');
        } else {
            $this->recursiveDeleteChunkDirectory($chunkPath);
        }
        return $this->getUploadPath() . DIRECTORY_SEPARATOR . $parameters['resumableFilename'];
    }

    protected function recursiveDeleteChunkDirectory($dir)
    {
        if (is_dir($dir)) {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(realpath($dir), FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST, RecursiveIteratorIterator::CATCH_GET_CHILD) as $object) {
                if ($object->isDir()) {
                    $this->recursiveDeleteChunkDirectory($object->getPathName());
                } else {
                    unlink($object->getPathName());
                }
            }
            rmdir($dir);
        }
    }
}
