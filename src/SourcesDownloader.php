<?php

namespace Wulkanowy\TimetablesListGenerator;

use GuzzleHttp\Client;

class SourcesDownloader
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $sourcesFilename;

    public function __construct(Client $client, string $sourcesFilename)
    {
        $this->client = $client;
        $this->sourcesFilename = $sourcesFilename;
    }

    public function download(string $target = '.')
    {
        $downloadList = $this->getSourcesArray();
        $all = count($downloadList);

        foreach ($downloadList as $key => $url) {
            $filename = pathinfo($url)['filename'].'.zip';
            echo '['.($key + 1).'/'.$all.'] Pobieranie '.$filename.'...';

            $this->client->request('GET', $url, [
                'sink' => rtrim($target, '/').'/'.$filename,
            ]);
            echo ' zrobione.'.PHP_EOL;
        }
    }

    public function getSourcesArray() : array
    {
        return json_decode(file_get_contents($this->sourcesFilename));
    }
}
