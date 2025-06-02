<?php

namespace App\Services;

use App\Http\HttpClient;
use App\Interfaces\ZonneplandataServiceInterface;
use DateTimeImmutable;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

class ZonneplandataService implements ZonneplandataServiceInterface {
    private HttpClient $httpClient;
    private array $config;
    private LoggerInterface $logger;

    public function __construct(HttpClient $httpClient, LoggerInterface $logger) {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->config = require __DIR__ . '/../../config/api.php';
    }

    /**
     * Validate that a date string matches YYYY-MM-DD format
     *
     * @param string $date The date string to validate
     * @throws InvalidArgumentException If the date is invalid
     */
    private function validateDate(string $date): void {
        // Check format using regex
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $message = 'Invalid date format. Date must be in YYYY-MM-DD format';
            $this->logger->error($message, ['date' => $date]);
            throw new InvalidArgumentException($message);
        }

        // Check if it's a valid date
        $dateTime = DateTimeImmutable::createFromFormat('Y-m-d', $date);
        if ($dateTime === false || $dateTime->format('Y-m-d') !== $date) {
            $message = 'Invalid date. Please provide a valid date';
            $this->logger->error($message, ['date' => $date]);
            throw new InvalidArgumentException($message);
        }
    }

    /**
     * Get electricity prices from the Zonneplan API
     *
     * @param string|null $date Optional date in YYYY-MM-DD format
     * @return array The API response data
     * @throws InvalidArgumentException If the date format is invalid
     */
    public function getData($type, ?string $date = null): array {
        $this->logger->info('Fetching {type} data', [
            'type' => $type,
            'date' => $date
        ]);
        $queryParams = [];
        if ($date !== null) {
            $this->validateDate($date);
            $queryParams['date'] = $date;
        }

        if ($type !== 'electricity' && $type !== 'gas') {
            $message = "Invalid type provided: {$type}";
            $this->logger->error($message);
            throw new InvalidArgumentException($message);
        }

        $endpoint = $this->config['zonneplan']['endpoints'][$type];
        $this->logger->debug('Making API request', [
            'endpoint' => $endpoint,
            'queryParams' => $queryParams
        ]);
        
        $response = $this->httpClient->get($endpoint, $queryParams);
        
        $this->logger->info('Successfully fetched {type} data', [
            'type' => $type,
            'dataPoints' => count($response['data'] ?? [])
        ]);
        
        return $response;
    }

    public function is_empty($data): bool {
        return empty($data['data']) || empty($data['data'][0]['total_price_tax_included']);
    }

}
