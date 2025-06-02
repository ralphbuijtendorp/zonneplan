<?php

namespace App\Controllers;

use App\Services\ResponseService;
use App\Interfaces\EnergyProviderInterface;
use App\Interfaces\DataServiceInterface;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class DataController extends BaseController {
    private LoggerInterface $logger;
    private EnergyProviderInterface $energyProvider;
    private DataServiceInterface $DataService;

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

    public function get_electricity_data(ServerRequestInterface $request): ResponseInterface {
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

                if ($this->energyProvider->is_empty($rawData)) {
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
                $this->DataService->save_actual_data_to_file($data_object, $filename);

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

    public function get_gas_data(ServerRequestInterface $request): ResponseInterface {
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

                if ($this->energyProvider->is_empty($rawData)) {
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
                $this->DataService->save_actual_data_to_file($data_object, $filename);

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
