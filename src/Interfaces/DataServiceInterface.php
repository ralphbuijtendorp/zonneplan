<?php

namespace App\Interfaces;

use App\DTOs\EnergyRecordsDTO;

interface DataServiceInterface
{
    /**
     * Process and enrich electricity data with additional information
     *
     * @param array $data Raw energy data
     * @return array Processed data with rankings and additional fields
     */
    public function processElectricityData(array $data): array;

    /**
     * Process and enrich gas data with additional information
     *
     * @param array $data Raw gas data
     * @return array Processed data with rankings
     */
    public function processGasData(array $data): array;

    /**
     * Get electricity records including lowest price, highest price, and highest sustainability
     *
     * @param array $data Processed electricity data
     * @return EnergyRecordsDTO
     */
    public function getElectricityRecords(array $data): EnergyRecordsDTO;

    /**
     * Get gas records including lowest and highest price
     *
     * @param array $data Processed gas data
     * @return EnergyRecordsDTO
     */
    public function getGasRecords(array $data): EnergyRecordsDTO;

    /**
     * Save data to a file
     *
     * @param array $data Data to save
     * @param string $filename Target filename
     * @return bool Success status
     * @throws \Exception If file write fails
     */
    public function save_actual_data_to_file(array $data, string $filename): bool;

    /**
     * Check if data exists in cache
     *
     * @param string $filename Cache filename to check
     * @return bool|array False if not found, array data if found
     */
    public function checkCache(string $filename): bool|array;

    /**
     * Get current date in Y-m-d format
     *
     * @return string
     */
    public function getCurrentDate(): string;

    /**
     * Create a filename for data storage
     *
     * @param string $type Type of data (electricity/gas)
     * @param string $date Date string
     * @return string
     */
    public function createFilename(string $type, string $date): string;
}
