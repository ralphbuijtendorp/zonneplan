<?php

namespace App\Controllers;

use App\Services\DataService;
use App\Services\ResponseService;
use App\Interfaces\EnergyProviderInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Controller responsible for handling scheduled data retrieval tasks.
 *
 * This controller manages periodic tasks for fetching and storing energy pricing data
 * from external providers. It handles both electricity and gas data retrieval,
 * processes the data, and stores it in the local cache.
 */
class CronjobController extends BaseController {
    /** @var LoggerInterface For logging cronjob operations */
    private LoggerInterface $logger;
    
    /** @var EnergyProviderInterface Service for fetching energy data from external sources */
    private EnergyProviderInterface $energyProvider;
    
    /** @var DataService Service for processing and storing energy data */
    private DataService $DataService;

    /**
     * Constructor for CronjobController
     *
     * @param ResponseService $responseService Service for creating HTTP responses
     * @param EnergyProviderInterface $energyProvider Service for fetching energy data
     * @param DataService $DataService Service for processing and storing data
     * @param LoggerInterface $logger Logging service
     */
    public function __construct(
        ResponseService $responseService,
        EnergyProviderInterface $energyProvider,
        DataService $DataService,
        LoggerInterface $logger
    ) {
        parent::__construct($responseService);
        $this->energyProvider = $energyProvider;
        $this->DataService = $DataService;
        $this->logger = $logger;
    }

    /**
     * Retrieves and stores electricity pricing data for today and tomorrow.
     *
     * This endpoint fetches electricity pricing data from the energy provider,
     * processes it, and stores it in the local cache. It handles data for both
     * the current day and the following day.
     *
     * @param ServerRequestInterface $request The incoming HTTP request
     * @return ResponseInterface Response indicating success or failure of the operation
     */
    public function store_electricity_data(ServerRequestInterface $request): ResponseInterface {
        $this->logger->info('Starting electricity data cronjob');

        $retrieval_dates = [];

        # Get today date
        $retrieval_dates[] = $this->DataService->getCurrentDate();
        
        $this->logger->debug('Processing dates', ['dates' => $retrieval_dates]);
        # Get tomorrow's date
        $retrieval_dates[] = $this->DataService->getTomorrowDate();

        try {
            foreach ($retrieval_dates as $retrieval_date) {

                # Retrieve the data from the external source
                $rawData = $this->energyProvider->getData('electricity', $retrieval_date);

                if ($this->energyProvider->is_empty($rawData)) {
                    $this->logger->warning('No electricity data available from API', [
                        'date' => $retrieval_date
                    ]);
                    return $this->response(['error' => "No date found for this date"], 404);
                }

                $processedData = $this->DataService->processElectricityData($rawData['data']);

                # Retrieve the low/high records from the data
                $records = $this->DataService->getElectricityRecords($processedData);

                # Return the final response
                $data_object = array(
                    "records" => $records,
                    "data" => $processedData
                );

                # Save the array as a json object
                $filename = $this->DataService->createFilename('electricity', $retrieval_date);
                $this->logger->debug('Saving electricity data to cache', [
                    'filename' => $filename,
                    'date' => $retrieval_date,
                    'recordCount' => count($processedData)
                ]);
                $this->DataService->save_actual_data_to_file($data_object, $filename);
            }
            return $this->response(array("result" => "Json object stored successfully"), 200);

        } catch (\Exception $e) {
            return $this->response(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Retrieves and stores gas pricing data for the current day.
     *
     * This endpoint fetches gas pricing data from the energy provider,
     * processes it, and stores it in the local cache.
     *
     * @param ServerRequestInterface $request The incoming HTTP request
     * @return ResponseInterface Response indicating success or failure of the operation
     */
    public function store_gas_data(ServerRequestInterface $request): ResponseInterface {
        $this->logger->info('Starting gas data cronjob');

        $retrieval_date = $this->DataService->getCurrentDate();
        $this->logger->debug('Processing date', ['date' => $retrieval_date]);

        try {
            # Retrieve the data from the external source
            $rawData = $this->energyProvider->getData('gas');

            if (!empty($rawData['data'])) {

                $this->logger->debug('Processing gas data');
                # Process the data using our service
                $processedData = $this->DataService->processGasData($rawData['data']);

                # Retrieve the low/high records from the data
                $records = $this->DataService->getGasRecords($processedData);

                # Return the final response
                $data_object = array(
                    "records" => $records,
                    "data" => $processedData
                );

                # Save the array as a json object
                $filename = $this->DataService->createFilename('gas', $retrieval_date);
                $this->logger->debug('Saving gas data to cache', [
                    'filename' => $filename,
                    'date' => $retrieval_date,
                    'recordCount' => count($processedData)
                ]);
                $this->DataService->save_actual_data_to_file($data_object, $filename);
            }

            return $this->response(array("result" => "Json object stored successfully"), 200);

        } catch (\Exception $e) {
            return $this->response(['error' => $e->getMessage()], 500);
        }
    }
}
