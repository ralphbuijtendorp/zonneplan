<?php
declare(strict_types=1);

namespace App\Services;

use Exception;
use App\DTOs\EnergyDataDTO;
use App\DTOs\EnergyRecordsDTO;
use App\Interfaces\DataServiceInterface;
use Psr\Log\LoggerInterface;

/**
 * Service responsible for processing and managing energy pricing data.
 *
 * This service handles the processing, ranking, and caching of electricity and gas
 * pricing data. It provides functionality for:
 * - Processing raw energy data with rankings and additional information
 * - Managing data caching to reduce API calls
 * - Identifying notable price points (highest/lowest prices, sustainability)
 * - File-based data persistence
 */
class DataService implements DataServiceInterface {
    /** @var LoggerInterface For logging service operations */
    private LoggerInterface $logger;

    /**
     * Constructor for DataService
     *
     * @param LoggerInterface $logger Service for logging operations
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Adds ranking information to energy data based on a specific key.
     *
     * @param array $data The array of energy data entries to rank
     * @param string $key The key in each entry to rank by (e.g., 'total_price_tax_included')
     * @param string $rankKey The key to store the ranking in each entry
     * @param bool $lower_is_better If true, lower values get better rankings
     * @return array The data array with added rankings
     */
    protected function addRanking(array $data, string $key, string $rankKey, bool $lower_is_better = true): array {
        $ranked = [];
        foreach ($data as $index => $item) {
            $ranked[$index] = ['value' => $item[$key], 'index' => $index];
        }

        if ($lower_is_better) {
            usort($ranked, function ($a, $b) { return $a['value'] <=> $b['value'];});
        } else {
            usort($ranked, function ($a, $b) { return $b['value'] <=> $a['value'];});
        }

        foreach ($ranked as $rank => $item) {
            $data[$item['index']][$rankKey] = $rank + 1;
        }

        return $data;
    }

    /**
     * Filters out entries with null values for a specific key.
     *
     * @param array $data Array of data entries to filter
     * @param string $key Key to check for null values
     * @return array Filtered array without null entries
     */
    protected function removeNullEntriesByKey(array $data, string $key): array {
        $filteredArray = [];
        foreach ($data as $item) {
            if (isset($item[$key]) && $item[$key] !== null) {
                $filteredArray[] = $item;
            }
        }

        return $filteredArray;
    }

    /**
     * Processes raw electricity data by adding rankings and filtering invalid entries.
     *
     * This method enriches electricity data with:
     * - Price rankings (lower is better)
     * - Sustainability rankings (higher is better)
     * - Removes entries with null prices
     *
     * @param array $data Raw electricity data from the provider
     * @return array Processed data with rankings and filtered entries
     */
    public function processElectricityData(array $data): array {
        $this->logger->info('Processing electricity data', [
            'recordCount' => count($data)
        ]);

        // Remove null entries first
        $processedData = $this->removeNullEntriesByKey($data, 'total_price_tax_included');
        $processedData = $this->addRanking($processedData, 'total_price_tax_included', 'rank_total_price', true);
        $processedData = $this->addRanking($processedData, 'sustainability_score', 'rank_sustainability_score', false);

        $this->logger->debug('Processed electricity data', [
            'processedRecords' => count($processedData)
        ]);

        return $processedData;
    }

    /**
     * Adds price and sustainability rankings to electricity data.
     *
     * @param array $data Electricity data to rank
     * @return array Data with added rankings
     */
    public function addElectricityRanking (array $data): array {
        // Add rankings
        $data = $this->addRanking($data, 'total_price_tax_included', 'rank_total_price');
        return $this->addRanking($data, 'sustainability_score', 'rank_sustainability_score', false);
    }

    /**
     * Processes raw gas data by adding rankings and filtering invalid entries.
     *
     * This method enriches gas data with:
     * - Price rankings (lower is better)
     * - Removes entries with null prices
     *
     * @param array $data Raw gas data from the provider
     * @return array Processed data with rankings and filtered entries
     */
    public function processGasData(array $data): array {
        $this->logger->info('Processing gas data', [
            'recordCount' => count($data)
        ]);

        // Remove null entries first
        $processedData = $this->removeNullEntriesByKey($data, 'total_price_tax_included');
        $processedData = $this->addRanking($processedData, 'total_price_tax_included', 'rank_total_price', true);

        $this->logger->debug('Processed gas data', [
            'processedRecords' => count($processedData)
        ]);

        return $processedData;
    }

    /**
     * Adds price rankings to gas data.
     *
     * @param array $data Gas data to rank
     * @return array Data with added rankings
     */
    public function addGasRanking (array $data): array {
        // Add rankings
        return $this->addRanking($data, 'total_price_tax_included', 'rank_total_price');
    }

    /**
     * Extracts notable electricity records from processed data.
     *
     * Finds and returns:
     * - Lowest price entry
     * - Highest price entry
     * - Most sustainable entry
     *
     * @param array $data Processed electricity data
     * @return EnergyRecordsDTO DTO containing notable records
     */
    public function getElectricityRecords(array $data): EnergyRecordsDTO {
        $lowest_price = $this->getEntryByRanking($data, 'total_price_tax_included', false);
        $highest_price = $this->getEntryByRanking($data, 'total_price_tax_included', true);
        $highest_sustainability = $this->getEntryByRanking($data, 'rank_sustainability_score', false);

        return new EnergyRecordsDTO(
            priceLow: EnergyDataDTO::fromArray($lowest_price),
            priceHigh: EnergyDataDTO::fromArray($highest_price),
            sustainabilityHigh: EnergyDataDTO::fromArray($highest_sustainability)
        );
    }

    /**
     * Extracts notable gas records from processed data.
     *
     * Finds and returns:
     * - Lowest price entry
     * - Highest price entry
     *
     * @param array $data Processed gas data
     * @return EnergyRecordsDTO DTO containing notable records
     */
    public function getGasRecords(array $data): EnergyRecordsDTO {
        $lowest_price = $this->getEntryByRanking($data, 'total_price_tax_included', false);
        $highest_price = $this->getEntryByRanking($data, 'total_price_tax_included', true);

        return new EnergyRecordsDTO(
            priceLow: EnergyDataDTO::fromArray($lowest_price),
            priceHigh: EnergyDataDTO::fromArray($highest_price)
        );
    }

    /**
     * Finds an entry with the highest or lowest value for a specific ranking key.
     *
     * @param array $dataArray Array of data entries to search
     * @param string $rankingKey Key to compare values by
     * @param bool $highest If true, finds highest value; if false, finds lowest
     * @return array The entry with the extreme value
     */
    protected function getEntryByRanking(array $dataArray, string $rankingKey, bool $highest = true): array {
        $bestEntry = $dataArray[0];
        foreach ($dataArray as $entry) {
            if (isset($entry[$rankingKey])) {
                $isBetter = $highest ? $entry[$rankingKey] > $bestEntry[$rankingKey] : $entry[$rankingKey] < $bestEntry[$rankingKey];

                if ($isBetter) {
                    $bestEntry = $entry;
                }
            }
        }
        return $bestEntry;
    }


    /**
     * @throws Exception
     */
    /**
     * Saves processed energy data to a JSON file.
     *
     * @param array $data The data to save
     * @param string $filename Target filename including path
     * @return bool True if save was successful, false otherwise
     * @throws Exception If file writing fails
     */
    public function save_actual_data_to_file(array $data, string $filename): bool {
        $this->logger->info('Saving data to file', [
            'filename' => $filename,
            'dataSize' => strlen(json_encode($data))
        ]);

        try {
            file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
            $this->logger->debug('Successfully saved data to file');
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to save data to file', [
                'error' => $e->getMessage(),
                'filename' => $filename
            ]);
            return false;
        }
    }

    /**
     * Gets the current date in Y-m-d format.
     *
     * @return string Current date
     */
    public function getCurrentDate(): string {
        return date('Y-m-d');
    }

    /**
     * Gets tomorrow's date in Y-m-d format.
     *
     * @return string Tomorrow's date
     */
    public function getTomorrowDate(): string {
        return date('Y-m-d', strtotime('+1 day'));
    }

    /**
     * Creates a standardized filename for energy data caching.
     *
     * @param string $type Type of energy data ('electricity' or 'gas')
     * @param string $date Date for the data in Y-m-d format
     * @return string Full path to the cache file
     */
    public function createFilename(string $type, string $date): string {
        return "./../data/data_{$type}_{$date}.json";
    }

    /**
     * Checks if cached data exists and returns it if found.
     *
     * @param string $filename Full path to the cache file
     * @return array|bool Array of cached data if found, false otherwise
     */
    public function checkCache(string $filename): array|bool {
        $this->logger->debug('Checking cache', ['filename' => $filename]);

        if (file_exists($filename)) {
            $this->logger->info('Cache hit', ['filename' => $filename]);
            return json_decode(file_get_contents($filename), true);
        }

        $this->logger->info('Cache miss', ['filename' => $filename]);
        return false;
    }

}
