<?php

namespace App\Services;

use App\Http\HttpClient;
use DateTimeImmutable;
use InvalidArgumentException;

class ZonneplandataService
{
    private HttpClient $httpClient;
    private array $config;

    public function __construct(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
        $this->config = require __DIR__ . '/../../config/api.php';
    }

    /**
     * Validate that a date string matches YYYY-MM-DD format
     * 
     * @param string $date The date string to validate
     * @throws InvalidArgumentException If the date is invalid
     */
    private function validateDate(string $date): void
    {
        // Check format using regex
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new InvalidArgumentException(
                'Invalid date format. Date must be in YYYY-MM-DD format'
            );
        }

        // Check if it's a valid date
        $dateTime = DateTimeImmutable::createFromFormat('Y-m-d', $date);
        if ($dateTime === false || $dateTime->format('Y-m-d') !== $date) {
            throw new InvalidArgumentException(
                'Invalid date. Please provide a valid date'
            );
        }
    }

    /**
     * Get electricity prices from the Zonneplan API
     * 
     * @param string|null $date Optional date in YYYY-MM-DD format
     * @return array The API response data
     * @throws InvalidArgumentException If the date format is invalid
     */
    public function getData(?string $date = null): array
    {
        $queryParams = [];
        if ($date !== null) {
            $this->validateDate($date);
            $queryParams['date'] = $date;
        }

        return $this->httpClient->get($this->config['zonneplan']['endpoints']['electricity_prices'], $queryParams);
    }
}
