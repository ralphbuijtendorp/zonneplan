<?php

namespace App\Services;

use Exception;

class DataService {

    function addRanking($data, $key, $rankKey, $lower_is_better = true): array {
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

    function removeNullEntriesByKey($data, $key): array {
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
    public function enrichEnergyData(array $data): array {
        // Remove null entries first
        $processedData = $this->removeNullEntriesByKey($data, 'total_price_tax_included');

        // Add rankings
        $processedData = $this->addRanking($processedData, 'total_price_tax_included', 'rank_total_price');
        return $this->addRanking($processedData, 'sustainability_score', 'rank_sustainability_score', false);
    }

    public function getRecords(array $data): array {

        $lowest_price = $this->getEntryByRanking($data, 'total_price_tax_included', false);
        $highest_price = $this->getEntryByRanking($data, 'total_price_tax_included', true);
        $highest_sustainability = $this->getEntryByRanking($data, 'rank_sustainability_score', false);

        return array(
            "price_low" => $lowest_price,
            "price_high" => $highest_price,
            "sustainability_high" => $highest_sustainability
        );
    }

    function getEntryByRanking($dataArray, $rankingKey, $highest = true) {
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
    function save_actual_data_to_file($data, $filename): bool {
        $jsonData = json_encode($data, JSON_PRETTY_PRINT);

        if (file_put_contents($filename, $jsonData)) {
            return true;
        } else {
            throw new Exception('Error writing file');
        }
    }

    function getCurrentDate(): string {
        return date('Y-m-d');
    }

    function getTomorrowDate(): string {
        return date('Y-m-d', strtotime('+1 day'));
    }

    function createFilename($date): string {
        return "./../data/data_{$date}.json";
    }

    function checkCache($filename): bool|array {
        if (file_exists($filename)) {
            return json_decode(file_get_contents($filename), true);
        }
        return false;
    }

}
