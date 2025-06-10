<?php

namespace App\Controllers;

use App\Services\ResponseService;
use App\Interfaces\EnergyProviderInterface;
use App\Interfaces\DataServiceInterface;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Controller for handling energy data retrieval requests.
 *
 * This controller provides endpoints for retrieving electricity and gas pricing data.
 * It implements a caching strategy to minimize external API calls and handles both
 * real-time and historical data requests.
 */
class DataController extends BaseController {
    /** @var LoggerInterface For logging data operations */
    private LoggerInterface $logger;
    
    /** @var EnergyProviderInterface Service for fetching energy data from external sources */
    private EnergyProviderInterface $energyProvider;
    
    /** @var DataServiceInterface Service for processing and managing energy data */
    private DataServiceInterface $DataService;

    /**
     * Constructor for DataController
     *
     * @param ResponseService $responseService Service for creating HTTP responses
     * @param EnergyProviderInterface $energyProvider Service for fetching energy data
     * @param DataServiceInterface $DataService Service for processing and managing data
     * @param LoggerInterface $logger Logging service
     */
    public function __construct(
        ResponseService $responseService,
        EnergyProviderInterface $energyProvider,
        DataServiceInterface $DataService,
        LoggerInterface $logger
    ) {
        parent::__construct($responseService);
        $this->energyProvider = $energyProvider;
        $this->DataService = $DataService;
        $this->logger = $logger;
    }

    /**
     * Retrieves electricity pricing data for a specific date.
     *
     * This endpoint returns electricity pricing data either from cache or by fetching
     * from the external provider if not cached. If no date is specified, it returns
     * data for the current date.
     *
     * @param ServerRequestInterface $request The incoming HTTP request with optional date parameter
     * @return ResponseInterface Response containing electricity pricing data or error
     */
    public function getElectricityData(ServerRequestInterface $request): ResponseInterface {
        $this->logger->info('Handling electricity data request', [
            'query' => $request->getQueryParams()
        ]);

        try {
            $queryParams = $request->getQueryParams();
            $date = $queryParams['date'] ?? null;

            if ($date == null) {
                $date = $this->DataService->getCurrentDate();
            }

            $filename = $this->DataService->createFilename('electricity', $date);

            # Check if we already have the requested data in cache
            # We could implement a redis construction here, but for simplicity it's a psychical cache on the same hard drive
            # This does eliminates an extra SPOF and a extra network element which also can cause delays
            $data_object = $this->DataService->checkCache($filename);

            if (!$data_object) {
                $this->logger->info('Fetching fresh electricity data');
                # Retrieve the data from the external source
                $rawData = $this->energyProvider->getData('electricity', $date);

                if ($this->energyProvider->isEmpty($rawData)) {
                    $this->logger->warning('Empty electricity data received', [
                        'date' => $date
                    ]);
                    return $this->response(['error' => "No date found for this date"], 404);
                }

                # Process the data using our service
                $processedData = $this->DataService->processElectricityData($rawData['data']);

                # Retrieve the low/high records from the data
                $records = $this->DataService->getElectricityRecords($processedData);

                # Return the final response
                $data_object = array(
                    "records" => $records->toArray(),
                    "data" => $processedData
                );

                # Save the array as a json object
                $this->DataService->saveActualDataToFile($data_object, $filename);

                $this->logger->info('Successfully processed and cached electricity data', [
                    'recordCount' => count($processedData)
                ]);
            } else {
                $this->logger->info('Returning cached electricity data');
            }

            return $this->response(array("data" => $data_object), 200);

        } catch (\Exception $e) {
            $this->logger->error('Error processing electricity data request', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->response(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Retrieves gas pricing data for a specific date.
     *
     * This endpoint returns gas pricing data either from cache or by fetching
     * from the external provider if not cached. It currently returns
     * data for the current date.
     *
     * @param ServerRequestInterface $request The incoming HTTP request with optional date parameter
     * @return ResponseInterface Response containing gas pricing data or error
     */
    public function getGasData(ServerRequestInterface $request): ResponseInterface {
        $this->logger->info('Handling gas data request', [
            'query' => $request->getQueryParams()
        ]);

        try {
            $queryParams = $request->getQueryParams();
            $date = $queryParams['date'] ?? null;

            if ($date == null) {
                $date = $this->DataService->getCurrentDate();
            }

            $filename = $this->DataService->createFilename('gas', $date);

            # Check if we already have the requested data in cache
            # We could implement a redis construction here, but for simplicity it's a psychical cache on the same hard drive
            # This does eliminates an extra SPOF and a extra network element which also can cause delays
            $data_object = $this->DataService->checkCache($filename);

            if (!$data_object) {
                $this->logger->info('Fetching fresh gas data');
                # Retrieve the data from the external source
                $rawData = $this->energyProvider->getData('gas');

                if ($this->energyProvider->isEmpty($rawData)) {
                    $this->logger->warning('Empty gas data received', [
                        'date' => $date
                    ]);
                    return $this->response(['error' => "No date found for this date"], 404);
                }

                # Process the data using our service
                $processedData = $this->DataService->processGasData($rawData['data']);

                # Retrieve the low/high records from the data
                $records = $this->DataService->getGasRecords($processedData);

                # Return the final response
                $data_object = array(
                    "records" => $records->toArray(),
                    "data" => $processedData
                );

                # Save the array as a json object
                $this->DataService->saveActualDataToFile($data_object, $filename);

                $this->logger->info('Successfully processed and cached gas data', [
                    'recordCount' => count($processedData)
                ]);
            } else {
                $this->logger->info('Returning cached gas data');
            }

            return $this->response(array("data" => $data_object), 200);

        } catch (\Exception $e) {
            $this->logger->error('Error processing gas data request', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->response(['error' => $e->getMessage()], 500);
        }
    }
}
