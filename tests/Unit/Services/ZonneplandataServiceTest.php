<?php

namespace Tests\Unit\Services;

use App\Http\HttpClient;
use App\Interfaces\ZonneplandataServiceInterface;
use App\Services\ZonneplandataService;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

class ZonneplandataServiceTest extends TestCase
{
    private ZonneplandataServiceInterface $zonneplandataService;
    private MockObject $httpClientMock;
    private MockObject $loggerMock;

    protected function setUp(): void
    {
        // Create mocks
        $this->httpClientMock = $this->createMock(HttpClient::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        
        // Create the service with the mocked dependencies
        $this->zonneplandataService = new ZonneplandataService(
            $this->httpClientMock,
            $this->loggerMock
        );
    }

    public function testGetDataWithValidElectricityDate(): void
    {
        $expectedData = [
            'data' => [
                [
                    'total_price_tax_included' => 0.25,
                    'sustainability_score' => 80
                ]
            ]
        ];

        // Configure the mock
        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with(
                $this->anything(),
                ['date' => '2025-06-01']
            )
            ->willReturn($expectedData);

        $result = $this->zonneplandataService->getData('electricity', '2025-06-01');
        
        $this->assertEquals($expectedData, $result);
    }

    public function testGetDataWithValidGasDate(): void
    {
        $expectedData = [
            'data' => [
                [
                    'total_price_tax_included' => 1.25
                ]
            ]
        ];

        // Configure the mock
        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with(
                $this->anything(),
                ['date' => '2025-06-01']
            )
            ->willReturn($expectedData);

        $result = $this->zonneplandataService->getData('gas', '2025-06-01');
        
        $this->assertEquals($expectedData, $result);
    }

    public function testGetDataWithInvalidDate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid date format. Date must be in YYYY-MM-DD format');
        
        $this->zonneplandataService->getData('electricity', 'invalid-date');
    }

    public function testGetDataWithInvalidType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid type provided');
        
        $this->zonneplandataService->getData('invalid-type', '2025-06-01');
    }

    public function testIsEmptyWithEmptyData(): void
    {
        $emptyData = ['data' => []];
        $this->assertTrue($this->zonneplandataService->is_empty($emptyData));
        
        $nullPriceData = ['data' => [['total_price_tax_included' => null]]];
        $this->assertTrue($this->zonneplandataService->is_empty($nullPriceData));
    }

    public function testIsEmptyWithValidData(): void
    {
        $validData = [
            'data' => [
                ['total_price_tax_included' => 0.25]
            ]
        ];
        
        $this->assertFalse($this->zonneplandataService->is_empty($validData));
    }
}
