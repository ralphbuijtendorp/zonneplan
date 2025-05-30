<?php

namespace App\Http;

use App\Config\Environment;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class HttpClient
{
    private Client $client;
    private array $config;

    public function __construct()
    {
        Environment::init();
        $this->config = require __DIR__ . '/../../config/api.php';
        $this->client = new Client(['base_uri' => $this->config['zonneplan']['base_url'],'timeout' => 10]);
    }

    public function get(string $endpoint, array $queryParams = []): array
    {
        try {
            $secret = $this->config['zonneplan']['secret'];
            
            if (empty($secret)) {
                throw new \RuntimeException('ZONNEPLAN_API_SECRET environment variable is not set');
            }

            $queryParams['secret'] = $secret;
            
            $response = $this->client->get($endpoint, ['query' => $queryParams]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            throw new \RuntimeException('Failed to fetch data from Zonneplan API: ' . $e->getMessage());
        }
    }
}
