<?php
declare(strict_types=1);

namespace Tests\Unit\Providers;

use App\Http\HttpClient;
use App\Interfaces\EnergyProviderInterface;
use App\Providers\ZonneplanProvider;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

class ZonneplanProviderTest extends TestCase
{
    private EnergyProviderInterface $energyProvider;
    private MockObject $httpClientMock;
    private MockObject $loggerMock;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        // Create mocks
        $this->httpClientMock = $this->createMock(HttpClient::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        
        // Create the provider with the mocked dependencies
        $this->energyProvider = new ZonneplanProvider(
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

        $result = $this->energyProvider->getData('electricity', '2025-06-01');
        
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

        $result = $this->energyProvider->getData('gas', '2025-06-01');
        
        $this->assertEquals($expectedData, $result);
    }

    public function testGetDataWithInvalidDate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid date format. Date must be in YYYY-MM-DD format');
        
        $this->energyProvider->getData('electricity', 'invalid-date');
    }

    public function testGetDataWithInvalidType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid type provided');
        
        $this->energyProvider->getData('invalid-type', '2025-06-01');
    }

    public function testIsEmptyWithEmptyData(): void
    {
        $emptyData = ['data' => []];
        $this->assertTrue($this->energyProvider->isEmpty($emptyData));
        
        $nullPriceData = ['data' => [['total_price_tax_included' => null]]];
        $this->assertTrue($this->energyProvider->isEmpty($nullPriceData));
    }

    public function testIsEmptyWithValidData(): void
    {
        $validData = [
            'data' => [
                ['total_price_tax_included' => 0.25]
            ]
        ];
        
        $this->assertFalse($this->energyProvider->isEmpty($validData));
    }
}
