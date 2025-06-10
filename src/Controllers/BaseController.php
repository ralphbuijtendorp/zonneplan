<?php

namespace App\Controllers;

use App\Services\ResponseService;
use Psr\Http\Message\ResponseInterface;

/**
 * Base controller class providing common functionality for all controllers.
 * 
 * This abstract class serves as a foundation for all controllers in the application,
 * providing standardized response handling through ResponseService.
 */
abstract class BaseController
{
    /** @var ResponseService Service for handling HTTP responses */
    protected ResponseService $responseService;

    /**
     * Constructor for BaseController
     * 
     * @param ResponseService $responseService Service for creating standardized HTTP responses
     */
    public function __construct(ResponseService $responseService)
    {
        $this->responseService = $responseService;
    }

    /**
     * Creates a standardized HTTP response
     * 
     * @param mixed $data Optional data to include in the response
     * @param int $statusCode HTTP status code, defaults to 200
     * @return ResponseInterface PSR-7 compliant response object
     */
    protected function response(mixed $data = null, int $statusCode = 200): ResponseInterface
    {
        return $this->responseService->createResponse($data, $statusCode);
    }
}
