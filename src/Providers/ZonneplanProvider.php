<?php
declare(strict_types=1);

namespace App\Providers;

use App\Http\HttpClient;
use App\Interfaces\EnergyProviderInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

class ZonneplanProvider implements EnergyProviderInterface
{
    private const ELECTRICITY_ENDPOINT = '/energy-prices/electricity/upcoming';
    private const GAS_ENDPOINT = '/energy-prices/gas/upcoming';

    private HttpClient $httpClient;
    private LoggerInterface $logger;

    public function __construct(HttpClient $httpClient, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    public function getData(string $type, ?string $date = null): array
    {
        $this->logger->debug('Retrieving energy data', [
            'type' => $type,
            'date' => $date
        ]);

        if ($date !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new InvalidArgumentException('Invalid date format. Date must be in YYYY-MM-DD format');
        }

        if (!in_array($type, ['electricity', 'gas'])) {
            throw new InvalidArgumentException('Invalid type provided');
        }

        $params = [];
        if ($date !== null) {
            $params['date'] = $date;
        }

        $endpoint = $type === 'electricity' ? self::ELECTRICITY_ENDPOINT : self::GAS_ENDPOINT;
        return $this->httpClient->get($endpoint, $params);
    }

    public function is_empty(array $data): bool
    {
        if (empty($data['data'])) {
            return true;
        }

        foreach ($data['data'] as $entry) {
            if (isset($entry['total_price_tax_included']) && $entry['total_price_tax_included'] !== null) {
                return false;
            }
        }

        return true;
    }
}
