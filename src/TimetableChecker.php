<?php

namespace Wulkanowy\TimetablesListGenerator;

use Colors\Color;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;

class TimetableChecker
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
        sort($this->schools);
    }

    public function check() : array
    {
        echo PHP_EOL;
        $c = new Color();
        $filtered = [];
        $requests = function (array $items) {
            foreach ($items as $key => $value) {
                yield new Request('GET', $value['timetables'][0]);
            }
        };
        $schools = $this->schools;
        $pool = new Pool($this->client, $requests($this->schools), [
            'concurrency' => 50,
            'fulfilled'   => function (ResponseInterface $response, $index) use ($schools, &$filtered, $c) {
                $value = $schools[$index];
                echo '['.$this->processed.'/'.$this->numberOfAll.'] '.$value['www'].' – ';
                $str = $response->getBody()->getContents();
                if (strpos($str, 'Plan lekcji Optivum firmy VULCAN') !== false) {
                    echo $c('Szkoła używa Planu lekcji Optivum.')->fg('green');
                    $value['url'] = $value['timetables'][0];
                    $filtered[$index] = $value;
                } else {
                    echo $c('nie wiem.')->fg('dark_gray');
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
            return $a['url'] <=> $b['url'];
        });

        return [
            'filtered'      => $filtered,
            'errors'        => $this->error,
            'clientErrors'  => $this->clientError,
            'connectErrors' => $this->connectError,
            'total'         => $this->numberOfAll,
        ];
    }
}
