<?php

namespace App\Services;


use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Response;

class ResponseService
{
    public function createResponse(mixed $data = null,  int $statusCode = 200): ResponseInterface {
        $apiResponse = $data;
        $response = new Response($statusCode);

        $response->getBody()->write(json_encode($apiResponse, JSON_PRETTY_PRINT) );
        
        return $response->withHeader('Content-Type', 'application/json');
    }
}
