<?php

namespace Wulkanowy\TimetablesListGenerator;

class NormalizeHelper
{
    public static function normalizeUrl(string $url) : string
    {
        $url = strtolower($url);
        $url = str_replace('@', '.', $url);
        $url = ltrim($url, 'https://');
        $url = ltrim($url, 'http://');
        $url = 'http://'.$url;

        return $url;
    }
}
