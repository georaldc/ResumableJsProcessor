<?php

namespace ResumableJsProcessor;

abstract class ResumableParametersContainerAbstract
{
    protected $mode;
    protected $parameters = [
        'resumableIdentifier' => '',
        'resumableFilename' => '',
        'resumableChunkNumber' => '',
        'resumableTotalSize' => 0,
        'resumableTotalChunks' => 0,
    ];
    
    public function __construct($mode)
    {
        $this->mode = $mode;
        $this->populateParameters();
    }

    abstract public function populateParameters();

    public function getMode()
    {
        return $this->mode;    
    }
    
    public function getParameters()
    {
        return $this->parameters;
    }
}
