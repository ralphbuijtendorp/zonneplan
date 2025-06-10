<?php
declare(strict_types=1);

namespace App\Providers;

use App\Http\HttpClient;
use App\Interfaces\EnergyProviderInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * Provider implementation for retrieving energy pricing data from Zonneplan API.
 *
 * This provider implements the EnergyProviderInterface to fetch electricity and gas
 * pricing data from Zonneplan's energy pricing API endpoints. It handles:
 * - Date-based queries for historical data
 * - Data validation and error handling
 * - Empty response detection
 */
class ZonneplanProvider implements EnergyProviderInterface
{
    /** @var string Endpoint for retrieving electricity pricing data */
    private const ELECTRICITY_ENDPOINT = '/energy-prices/electricity/upcoming';
    
    /** @var string Endpoint for retrieving gas pricing data */
    private const GAS_ENDPOINT = '/energy-prices/gas/upcoming';

    /** @var HttpClient Client for making HTTP requests to the Zonneplan API */
    private HttpClient $httpClient;
    
    /** @var LoggerInterface Logger for tracking API operations */
    private LoggerInterface $logger;

    /**
     * Constructor for ZonneplanProvider
     *
     * @param HttpClient $httpClient Client for making HTTP requests
     * @param LoggerInterface $logger Logger for tracking operations
     */
    public function __construct(HttpClient $httpClient, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    /**
     * Retrieves energy pricing data from the Zonneplan API.
     *
     * Fetches either electricity or gas pricing data for a specific date.
     * If no date is provided, returns data for the current period.
     *
     * @param string $type Type of energy data to retrieve ('electricity' or 'gas')
     * @param string|null $date Optional date in YYYY-MM-DD format
     * @return array Raw API response data
     * @throws InvalidArgumentException If type is invalid or date format is incorrect
     */
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

    /**
     * Checks if the API response data is effectively empty.
     *
     * Considers data empty if:
     * - The 'data' key is empty or missing
     * - All entries have null or missing 'total_price_tax_included'
     *
     * @param array $data API response data to check
     * @return bool True if data is effectively empty, false otherwise
     */

    public function isEmpty(array $data): bool
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
