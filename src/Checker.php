<?php

namespace Wulkanowy\TimetablesListGenerator;

use Colors\Color;
use DOMDocument;
use DOMXPath;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;

class Checker
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var array
     */
    private $schools;

    /**
     * @var int
     */
    private $numberOfAll;

    /**
     * @var int
     */
    private $processed = 0;

    /**
     * @var int
     */
    private $connectError = 0;

    /**
     * @var int
     */
    private $clientError = 0;

    /**
     * @var int
     */
    private $error = 0;

    public function __construct(Client $client, array $schools)
    {
        $this->client = $client;
        $this->schools = $schools;
        $this->numberOfAll = count($schools);
    }

    public function check() : array
    {
        echo PHP_EOL;
        $c = new Color();
        $filtered = [];
        $timetables = [];
        $requests = function (array $items) {
            foreach ($items as $key => $value) {
                yield new Request('GET', $value['www']);
            }
        };
        $schools = $this->schools;
        $pool = new Pool($this->client, $requests($this->schools), [
            'concurrency' => 50,
            'fulfilled'   => function (ResponseInterface $response, $index) use ($schools, &$filtered, &$timetables, $c) {
                $value = $schools[$index];
                $path = $value['www'];
                echo '['.$this->processed.'/'.$this->numberOfAll.'] '.$path.' – ';
                if (stripos($response->getBody(), 'plan lekcji') === false &&
                    stripos($response->getBody(), 'plan zajęć') === false &&
	                stripos($response->getBody(), 'plan') === false &&
	                stripos($response->getBody(), 'podział godzin') === false &&
	                stripos($response->getBody(), 'podział') === false) {
                    echo $c('Nie znaleziono planu lekcji.')->fg('dark_gray');
                } else {
                    echo $c('Jest plan lekcji!')->fg('green');
                    $urls = $this->getTimetableUrl($response->getBody(), $value['www']);
                    if (isset($urls[0])) {
                        echo $c(' '.$urls[0]);
                    } else {
                        echo $c(' ale jakby go nie było')->fg('red');
                    }
                    $value['timetables'] = $urls;
                    $filtered[$index] = $value;

                    $timetables[$index] = [
                        'www'        => $value['www'],
                        'name'       => $value['name'],
                        'timetables' => $urls,
                    ];
                }
                $this->processed++;
                echo PHP_EOL;
            },
            'rejected' => function (RequestException $e, $index) use ($schools, $c) {
                $value = $schools[$index];
                $path = $value['www'];
                echo '['.$this->processed.'/'.$this->numberOfAll.'] '.$path.' – ';

                $this->error++;
                $this->processed++;
                if ($e instanceof ConnectException) {
                    $this->connectError++;
                    echo $c('Nie nazwiązano połączenia')->fg('red');
                } elseif ($e instanceof ClientException) {
                    $this->clientError++;
                    echo $c('Błąd połączenia')->fg('red');
                } else {
                    echo $c('Inny błąd')->fg('yellow');
                }
                echo PHP_EOL;
            },
        ]);

        try {
            $pool->promise()->wait();
        } catch (Exception $e) {
            echo $e->getMessage().PHP_EOL;
        }
        usort($filtered, function ($a, $b) {
            return $a['www'] <=> $b['www'];
        });

        return [
            'filtered'      => $filtered,
            'timetables'    => $timetables,
            'errors'        => $this->error,
            'clientErrors'  => $this->clientError,
            'connectErrors' => $this->connectError,
            'total'         => $this->numberOfAll,
        ];
    }

    private function getTimetableUrl(string $html, string $fullUrl) : array
    {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML($html);

        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query('/html/body//a');

        $timetableList = [];

        foreach ($nodes as $node) {
            /** @var \DOMElement $node */
            if (stripos($node->textContent, 'plan lekcji') !== false ||
                stripos($node->textContent, 'plan zajęć') !== false ||
	            stripos($node->textContent, 'plan') !== false ||
	            stripos($node->textContent, 'podział godzin') !== false ||
	            stripos($node->textContent, 'podział') !== false) {
                $url = $node->getAttribute('href');

                if (0 === strpos($url, $fullUrl)) {
                    $url = substr($url, strlen($fullUrl));
                }

                if (filter_var($url, FILTER_VALIDATE_URL)) {
                    $timetableList[] = $url;
                } else {
                    $timetableList[] = rtrim($fullUrl, '/').'/'.ltrim($url, '/');
                }
            }
        }

        return array_unique($timetableList);
    }
}
