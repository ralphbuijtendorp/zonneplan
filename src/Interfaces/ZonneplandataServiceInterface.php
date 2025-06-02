<?php

namespace App\Interfaces;

use InvalidArgumentException;

interface ZonneplandataServiceInterface
{
    /**
     * Get data from the energy provider API
     *
     * @param string $type Type of data to retrieve ('electricity' or 'gas')
     * @param string|null $date Optional date in YYYY-MM-DD format
     * @return array The API response data
     * @throws InvalidArgumentException If the date format is invalid or type is invalid
     */
    public function getData(string $type, ?string $date = null): array;

    /**
     * Check if the response data is empty
     *
     * @param array $data The response data to check
     * @return bool True if the data is empty
     */
    public function is_empty(array $data): bool;
}
