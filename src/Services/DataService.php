<?php
declare(strict_types=1);

namespace App\Services;

use Exception;
use App\DTOs\EnergyDataDTO;
use App\DTOs\EnergyRecordsDTO;
use App\Interfaces\DataServiceInterface;
use Psr\Log\LoggerInterface;

class DataService implements DataServiceInterface {
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

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
     * Process and enrich energy data with additional information
     *
     * @param array $data Raw energy data
     * @return array Processed data with rankings and additional fields
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

    public function addElectricityRanking (array $data): array {
        // Add rankings
        $data = $this->addRanking($data, 'total_price_tax_included', 'rank_total_price');
        return $this->addRanking($data, 'sustainability_score', 'rank_sustainability_score', false);
    }

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

    public function addGasRanking (array $data): array {
        // Add rankings
        return $this->addRanking($data, 'total_price_tax_included', 'rank_total_price');
    }

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

    public function getGasRecords(array $data): EnergyRecordsDTO {
        $lowest_price = $this->getEntryByRanking($data, 'total_price_tax_included', false);
        $highest_price = $this->getEntryByRanking($data, 'total_price_tax_included', true);

        return new EnergyRecordsDTO(
            priceLow: EnergyDataDTO::fromArray($lowest_price),
            priceHigh: EnergyDataDTO::fromArray($highest_price)
        );
    }

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

    public function getCurrentDate(): string {
        return date('Y-m-d');
    }

    public function getTomorrowDate(): string {
        return date('Y-m-d', strtotime('+1 day'));
    }

    public function createFilename(string $type, string $date): string {
        return "./../data/data_{$type}_{$date}.json";
    }

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
