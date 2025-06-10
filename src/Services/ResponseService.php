<?php

namespace App\Services;


use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Response;

/**
 * Service responsible for creating standardized HTTP responses.
 *
 * This service provides a consistent way to create JSON responses across the application.
 * It ensures that all responses follow the same format and include proper headers.
 * The service implements PSR-7 compliant responses using Slim's Response implementation.
 */
class ResponseService
{
    /**
     * Creates a standardized JSON HTTP response.
     *
     * This method takes any data and converts it to a JSON response with proper headers.
     * The response follows PSR-7 standards and includes:
     * - JSON encoded body with pretty printing
     * - application/json Content-Type header
     * - Configurable HTTP status code
     *
     * @param mixed $data Data to be JSON encoded in the response body
     * @param int $statusCode HTTP status code (defaults to 200 OK)
     * @return ResponseInterface PSR-7 compliant response object
     */
    public function createResponse(mixed $data = null,  int $statusCode = 200): ResponseInterface {
        $apiResponse = $data;
        $response = new Response($statusCode);

        $response->getBody()->write(json_encode($apiResponse, JSON_PRETTY_PRINT) );
        
        return $response->withHeader('Content-Type', 'application/json');
    }
}
