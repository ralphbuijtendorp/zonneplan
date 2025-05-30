<?php

namespace App\Controllers;

use App\Services\DataService;
use App\Services\ResponseService;
use App\Services\ZonneplandataService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class DataController extends BaseController {
    private ZonneplandataService $ZonneplandataService;
    private DataService $DataService;

    public function __construct(ResponseService $responseFactory, ZonneplandataService $ZonneplandataService, DataService $DataService) {
        parent::__construct($responseFactory);
        $this->ZonneplandataService = $ZonneplandataService;
        $this->DataService = $DataService;
    }

    // This is the main function which represents the /get_actual_data endpoint
    public function store_actual_data(ServerRequestInterface $request): ResponseInterface {

        $retrieval_dates = [];

        # Get today date
        $retrieval_dates[] = $this->DataService->getCurrentDate();
        # Get tomorrow's date
        $retrieval_dates[] = $this->DataService->getTomorrowDate();

        try {
            foreach ($retrieval_dates as $retrieval_date) {

                # Retrieve the data from the external source
                $rawData = $this->ZonneplandataService->getData($retrieval_date);

                if (!empty($rawData['data'])) {

                    # Process the data using our service
                    $processedData = $this->DataService->enrichEnergyData($rawData['data']);

                    # Retrieve the low/high records from the data
                    $records = $this->DataService->getRecords($processedData);

                    # Return the final response
                    $data_object = array(
                        "records" => $records,
                        "data" => $processedData
                    );

                    # Save the array as a json object
                    $filename = $this->DataService->createFilename($retrieval_date);
                    $this->DataService->save_actual_data_to_file($data_object, $filename);
                }
            }
            return $this->response(array("result" => "Json object stored successfully"), 200);

        } catch (\Exception $e) {
            return $this->response(['error' => $e->getMessage()], 500);
        }
    }

    public function get_data(ServerRequestInterface $request): ResponseInterface {
        $queryParams = $request->getQueryParams();
        $date = $queryParams['date'] ?? null;

        if ($date == null) {
            $date = $this->DataService->getCurrentDate();
        }

        $filename = $this->DataService->createFilename($date);

        # Check if we already have the requested data in cache
        # We could implement a redis construction here, but for simplicity it's a psychical cache on the same hard drive
        # This does eliminates an extra SPOF and a extra network element which also can cause delays
        $data_object = $this->DataService->checkCache($filename);

        if (!$data_object) {
            try {
                # Retrieve the data from the external source
                $rawData = $this->ZonneplandataService->getData($date);

                if (empty($rawData['data'])) {
                    return $this->response(['error' => "No date found for this date"], 404);
                }

                # Process the data using our service
                $processedData = $this->DataService->enrichEnergyData($rawData['data']);

                # Retrieve the low/high records from the data
                $records = $this->DataService->getRecords($processedData);

                # Return the final response
                $data_object = array(
                    "records" => $records,
                    "data" => $processedData
                );

                # Save the array as a json object
                $this->DataService->save_actual_data_to_file($data_object, $filename);

            } catch (\Exception $e) {
                return $this->response(['error' => $e->getMessage()],500);
            }
        }
        return $this->response(array("data" => $data_object), 200);
    }
}
