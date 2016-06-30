# ResumableJsProcessor
[![Build Status](https://travis-ci.org/georaldc/ResumableJsProcessor.svg?branch=master)](https://travis-ci.org/georaldc/ResumableJsProcessor)

Easily handle [resumable.js](https://github.com/23/resumable.js/) uploads.

Inside target server file for resumable.js, instantiate the ResumableJsProcessor class with your intended upload path specified, pass the appropriate mode (supports 2 modes at the moment, see ResumableJsProcessor constants) and call the process() method.

```php
$resumable = new ResumableJsProcessor('path/to/uploads');
if (isset($_FILES)) {
    $resumable->setMode(ResumableJsProcessor::MODE_UPLOAD_CHUNK);
} else {
    $resumable->setMode(ResumableJsProcessor::MODE_TEST_CHUNK);
}
$fileUploaded = $resumable->process();
if (false !== $fileUploaded) {
  // $fileUploaded will contain the upload path + filename of file that has been uploaded. You may do further processing here
}
```

This file will be called multiple times for every chunk checked and uploaded. Once ResumableJsProcessor::process() returns a string, that will be indication that the chunks have been uploaded and assembled.
