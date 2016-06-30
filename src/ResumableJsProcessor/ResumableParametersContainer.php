<?php

namespace ResumableJsProcessor;

class ResumableParametersContainer extends ResumableParametersContainerAbstract
{
    public function populateParameters()
    {
        $mode = $this->getMode();
        if (ResumableJsProcessor::MODE_TEST_CHUNK === $mode) {
            if (isset($_GET['resumableIdentifier']) && trim($_GET['resumableIdentifier']) != '') {
                $this->parameters['resumableIdentifier'] = trim($_GET['resumableIdentifier']);
            }
            if (isset($_GET['resumableFilename']) && trim($_GET['resumableFilename']) != '') {
                $this->parameters['resumableFilename'] = trim($_GET['resumableFilename']);
            }
            if (isset($_GET['resumableChunkNumber']) && trim($_GET['resumableChunkNumber']) != '') {
                $this->parameters['resumableChunkNumber'] = (int) $_GET['resumableChunkNumber'];
            }
            if (isset($_GET['resumableTotalSize']) && trim($_GET['resumableTotalSize']) != '') {
                $this->parameters['resumableTotalSize'] = (int) $_GET['resumableTotalSize'];
            }
            if (isset($_GET['resumableTotalChunks']) && trim($_GET['resumableTotalChunks']) != '') {
                $this->parameters['resumableTotalChunks'] = (int) $_GET['resumableTotalChunks'];
            }
        } elseif (ResumableJsProcessor::MODE_UPLOAD_CHUNK === $mode) {
            if (isset($_POST['resumableIdentifier']) && trim($_POST['resumableIdentifier']) != '') {
                $this->parameters['resumableIdentifier'] = trim($_POST['resumableIdentifier']);
            }
            if (isset($_POST['resumableFilename']) && trim($_POST['resumableFilename']) != '') {
                $this->parameters['resumableFilename'] = trim($_POST['resumableFilename']);
            }
            if (isset($_POST['resumableChunkNumber']) && trim($_POST['resumableChunkNumber']) != '') {
                $this->parameters['resumableChunkNumber'] = (int) $_POST['resumableChunkNumber'];
            }
            if (isset($_POST['resumableTotalSize']) && trim($_POST['resumableTotalSize']) != '') {
                $this->parameters['resumableTotalSize'] = (int) $_POST['resumableTotalSize'];
            }
            if (isset($_POST['resumableTotalChunks']) && trim($_POST['resumableTotalChunks']) != '') {
                $this->parameters['resumableTotalChunks'] = (int) $_POST['resumableTotalChunks'];
            }
        }
    }
}