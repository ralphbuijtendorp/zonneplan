<?php

namespace App\Controllers;

use App\Services\DataService;
use App\Services\ResponseService;
use App\Services\ZonneplandataService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CronjobController extends BaseController {
    private ZonneplandataService $ZonneplandataService;
    private DataService $DataService;

    public function __construct(ResponseService $responseFactory, ZonneplandataService $ZonneplandataService, DataService $DataService) {
        parent::__construct($responseFactory);
        $this->ZonneplandataService = $ZonneplandataService;
        $this->DataService = $DataService;
    }

    // This is the function to retrieve the electricity data for today and tomorrow
    public function store_electricity_data(ServerRequestInterface $request): ResponseInterface {

        $retrieval_dates = [];

        # Get today date
        $retrieval_dates[] = $this->DataService->getCurrentDate();
        # Get tomorrow's date
        $retrieval_dates[] = $this->DataService->getTomorrowDate();

        try {
            foreach ($retrieval_dates as $retrieval_date) {

                # Retrieve the data from the external source
                $rawData = $this->ZonneplandataService->getData('electricity', $retrieval_date);

                if (!$this->ZonneplandataService->is_empty($rawData)) {

                    # Process the data using our service
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
                    $this->DataService->save_actual_data_to_file($data_object, $filename);
                }
            }
            return $this->response(array("result" => "Json object stored successfully"), 200);

        } catch (\Exception $e) {
            return $this->response(['error' => $e->getMessage()], 500);
        }
    }

    // This is the function to retrieve the electricity data for today and tomorrow
    public function store_gas_data(ServerRequestInterface $request): ResponseInterface {

        $retrieval_date = $this->DataService->getCurrentDate();

        try {
            # Retrieve the data from the external source
            $rawData = $this->ZonneplandataService->getData('gas');

            if (!empty($rawData['data'])) {

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
                $this->DataService->save_actual_data_to_file($data_object, $filename);
            }

            return $this->response(array("result" => "Json object stored successfully"), 200);

        } catch (\Exception $e) {
            return $this->response(['error' => $e->getMessage()], 500);
        }
    }
}
