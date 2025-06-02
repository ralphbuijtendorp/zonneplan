<?php
declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\DataService;
use App\DTOs\EnergyDataDTO;
use App\DTOs\EnergyRecordsDTO;
use App\Interfaces\DataServiceInterface;
use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Class to expose protected methods for testing
 */
class TestableDataService extends DataService {
    public function publicRemoveNullEntriesByKey(array $data, string $key): array {
        return $this->removeNullEntriesByKey($data, $key);
    }

    public function publicAddRanking(array $data, string $key, string $rankKey, bool $lower_is_better = true): array {
        return $this->addRanking($data, $key, $rankKey, $lower_is_better);
    }

    public function publicGetEntryByRanking(array $dataArray, string $rankingKey, bool $highest = true): array {
        return $this->getEntryByRanking($dataArray, $rankingKey, $highest);
    }
}

class DataServiceTest extends TestCase
{
    private DataServiceInterface $dataService;

    protected function setUp(): void
    {
        $loggerMock = $this->createMock(LoggerInterface::class);
        $this->dataService = new TestableDataService($loggerMock);
    }

    public function testRemoveNullEntriesByKey(): void
    {
        $testData = [
            ['id' => 1, 'price' => 100],
            ['id' => 2, 'price' => null],
            ['id' => 3, 'price' => 300],
        ];

        $result = $this->dataService->publicRemoveNullEntriesByKey($testData, 'price');

        $this->assertCount(2, $result);
        $this->assertEquals(100, $result[0]['price']);
        $this->assertEquals(300, $result[1]['price']);
    }

    public function testAddRanking(): void
    {
        $testData = [
            ['id' => 1, 'price' => 300],
            ['id' => 2, 'price' => 100],
            ['id' => 3, 'price' => 200],
        ];

        $result = $this->dataService->publicAddRanking($testData, 'price', 'rank', true);

        $this->assertEquals(1, $result[1]['rank']);
        $this->assertEquals(2, $result[2]['rank']);
        $this->assertEquals(3, $result[0]['rank']);
    }

    public function testAddRankingHigherIsBetter(): void
    {
        $testData = [
            ['id' => 1, 'price' => 300],
            ['id' => 2, 'price' => 100],
            ['id' => 3, 'price' => 200],
        ];

        $result = $this->dataService->publicAddRanking($testData, 'price', 'rank', false);

        $this->assertEquals(3, $result[1]['rank']);
        $this->assertEquals(2, $result[2]['rank']);
        $this->assertEquals(1, $result[0]['rank']);
    }

    public function testEnrichEnergyData(): void
    {
        $testData = [
            [
                'total_price_tax_included' => 100,
                'sustainability_score' => 80
            ],
            [
                'total_price_tax_included' => 200,
                'sustainability_score' => 90
            ],
            [
                'total_price_tax_included' => null,
                'sustainability_score' => 70
            ]
        ];

        $result = $this->dataService->processElectricityData($testData);

        $this->assertCount(2, $result);
        $this->assertEquals(1, $result[0]['rank_total_price']);
        $this->assertEquals(2, $result[1]['rank_total_price']);
        $this->assertEquals(1, $result[1]['rank_sustainability_score']);
        $this->assertEquals(2, $result[0]['rank_sustainability_score']);
    }

    public function testGetElectricityRecords(): void
    {
        $testData = [
            [
                'total_price_tax_included' => 100,
                'sustainability_score' => 80,
                'rank_sustainability_score' => 2
            ],
            [
                'total_price_tax_included' => 200,
                'sustainability_score' => 90,
                'rank_sustainability_score' => 1
            ]
        ];

        $result = $this->dataService->getElectricityRecords($testData);

        $this->assertInstanceOf(EnergyRecordsDTO::class, $result);
        $this->assertInstanceOf(EnergyDataDTO::class, $result->priceLow);
        $this->assertInstanceOf(EnergyDataDTO::class, $result->priceHigh);
        $this->assertInstanceOf(EnergyDataDTO::class, $result->sustainabilityHigh);
        
        $this->assertEquals(100, $result->priceLow->totalPriceTaxIncluded);
        $this->assertEquals(200, $result->priceHigh->totalPriceTaxIncluded);
        $this->assertEquals(90, $result->sustainabilityHigh->sustainabilityScore);
    }

    public function testGetGasRecords(): void
    {
        $testData = [
            [
                'total_price_tax_included' => 100
            ],
            [
                'total_price_tax_included' => 200
            ]
        ];

        $result = $this->dataService->getGasRecords($testData);

        $this->assertInstanceOf(EnergyRecordsDTO::class, $result);
        $this->assertInstanceOf(EnergyDataDTO::class, $result->priceLow);
        $this->assertInstanceOf(EnergyDataDTO::class, $result->priceHigh);
        $this->assertNull($result->sustainabilityHigh);
        
        $this->assertEquals(100, $result->priceLow->totalPriceTaxIncluded);
        $this->assertEquals(200, $result->priceHigh->totalPriceTaxIncluded);
    }

    public function testGetEntryByRanking(): void
    {
        $testData = [
            ['value' => 100, 'rank' => 1],
            ['value' => 200, 'rank' => 2],
            ['value' => 300, 'rank' => 3]
        ];

        $highest = $this->dataService->publicGetEntryByRanking($testData, 'rank', true);
        $lowest = $this->dataService->publicGetEntryByRanking($testData, 'rank', false);

        $this->assertEquals(3, $highest['rank']);
        $this->assertEquals(1, $lowest['rank']);
    }

    /**
     * @throws Exception
     */
    public function testSaveActualDataToFile(): void
    {
        $testData = ['test' => 'data'];
        $tempFile = sys_get_temp_dir() . '/test_data.json';

        $result = $this->dataService->save_actual_data_to_file($testData, $tempFile);
        $this->assertTrue($result);
        $this->assertTrue(file_exists($tempFile));
        $this->assertEquals($testData, json_decode(file_get_contents($tempFile), true));

        unlink($tempFile); // Cleanup
    }

    public function testCreateFilename(): void
    {
        $date = '2025-06-01';
        
        // Test gas filename
        $gasResult = $this->dataService->createFilename('gas', $date);
        $this->assertEquals('./../data/data_gas_2025-06-01.json', $gasResult);

        // Test electricity filename
        $electricityResult = $this->dataService->createFilename('electricity', $date);
        $this->assertEquals('./../data/data_electricity_2025-06-01.json', $electricityResult);
    }

    public function testCheckCache(): void
    {
        $testData = ['test' => 'data'];
        $tempFile = sys_get_temp_dir() . '/test_cache.json';
        file_put_contents($tempFile, json_encode($testData));

        $result = $this->dataService->checkCache($tempFile);
        $this->assertEquals($testData, $result);

        unlink($tempFile);

        $result = $this->dataService->checkCache('non_existent_file.json');
        $this->assertFalse($result);
    }
}
