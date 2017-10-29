<?php

namespace Wulkanowy\TimetablesListGenerator;

use InvalidArgumentException;
use ZipArchive;

class ZipExtractor
{
    public function __construct(string $filename)
    {
        if (!file_exists($filename)) {
            throw new InvalidArgumentException('Zip file not found!');
        }

        $this->filename = $filename;
    }

    public function extract(string $target) : bool
    {
        $zip = new ZipArchive();

        if ($zip->open($this->filename) === true) {
            $zip->extractTo($target);
            $zip->close();

            return true;
        }

        return false;
    }
}
