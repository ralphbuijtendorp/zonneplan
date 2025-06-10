<?php

namespace App\Http;

use App\Config\Environment;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class HttpClient
{
    private Client $client;
    private array $config;
    private const MAX_RETRIES = 3;
    private const RETRY_DELAY_MS = 1000; // 1 second

    public function __construct()
    {
        Environment::init();
        $this->config = require __DIR__ . '/../../config/api.php';
        $this->client = new Client(['base_uri' => $this->config['zonneplan']['base_url'],'timeout' => 10]);
    }

    public function get(string $endpoint, array $queryParams = []): array
    {
        $attempt = 1;
        $lastException = null;

        while ($attempt <= self::MAX_RETRIES) {
            try {
                $secret = $this->config['zonneplan']['secret'];
                
                if (empty($secret)) {
                    throw new \RuntimeException('ZONNEPLAN_API_SECRET environment variable is not set');
                }

                $queryParams['secret'] = $secret;
                
                $response = $this->client->get($endpoint, ['query' => $queryParams]);
                $data = json_decode($response->getBody()->getContents(), true);

                return $data;

            } catch (GuzzleException $e) {
                $lastException = $e;
                
                if ($attempt < self::MAX_RETRIES) {
                    $delay = self::RETRY_DELAY_MS * pow(2, $attempt - 1);
                    usleep($delay * 1000);
                }
                
                $attempt++;
            }
        }

        throw new \RuntimeException(
            sprintf(
                'Failed to fetch data from Zonneplan API after %d attempts: %s',
                self::MAX_RETRIES,
                $lastException->getMessage()
            )
        );
    }
}
