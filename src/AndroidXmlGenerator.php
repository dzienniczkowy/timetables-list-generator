<?php

namespace Wulkanowy\TimetablesListGenerator;

use DOMDocument;
use SimpleXMLElement;

class AndroidXmlGenerator implements GeneratorInterface
{
    private $timetables;

    public function __construct(array $timetables)
    {
        $this->timetables = $timetables;
    }

    public function save(string $filename) : bool
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><resources/>');
        $xml->addAttribute('xmlns:xmlns:tools', 'http://schemas.android.com/tools');
        $xml->addAttribute('android:tools:ignore', 'MissingTranslation');

        $timetableSchools = $xml->addChild('string-array');
        $timetableSchools->addAttribute('name', 'names');

        foreach ($this->timetables as $name) {
            $timetableSchools->addChild('item', $name['name']);
        }

        $timetableUrls = $xml->addChild('string-array');
        $timetableUrls->addAttribute('name', 'urls');

        foreach ($this->timetables as $name) {
            $timetableUrls->addChild('item', htmlspecialchars($name['url']));
        }

        $dom = new DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());

        $output = preg_replace_callback('/^( +)</m', function ($a) {
            return str_repeat(' ', (int) (strlen($a[1]) / 2) * 4).'<';
        }, $dom->saveXML());

        return file_put_contents($filename, $output);
    }
}
