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
                yield new Request('GET', $value['www'], [
                    'connect_timeout' => 3.14,
                ]);
            }
        };
        $schools = $this->schools;
        $pool = new Pool($this->client, $requests($this->schools), [
            'concurrency' => 50,
            'fulfilled'   => function (ResponseInterface $response, $index) use ($schools, &$filtered, $c) {
                $value = $schools[$index];
                $path = $value['www'];
                echo '['.$this->processed.'/'.$this->numberOfAll.'] '.$path.' – ';
                if (strpos($response->getBody(), 'Plan lekcji') === false) {
                    echo $c('Nie znaleziono planu lekcji.')->fg('dark_gray');
                } else {
                    echo $c('Jest plan lekcji!')->fg('green');
                    $timetableUrls = $this->getTimetableUrl($response->getBody(), $value['www']);
                    $timetables[$index] = $timetableUrls;
                    if (isset($timetableUrls[0])) {
                        echo $c(' '.$timetableUrls[0]);
                    } else {
                        echo $c(' ale jakby go nie było')->fg('red');
                    }
                    $value['timetables'] = $timetableUrls;
                    $filtered[$index] = $value;
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
            if (strpos(strtolower(trim($node->textContent)), 'plan lekcji') !== false) {
                $url = $node->getAttribute('href');

                if (substr($url, 0, strlen($fullUrl)) == $fullUrl) {
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
