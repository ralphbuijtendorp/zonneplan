<?php

namespace App\Controllers;

use App\Services\ResponseService;
use Psr\Http\Message\ResponseInterface;

abstract class BaseController
{
    protected ResponseService $responseService;

    public function __construct(ResponseService $responseService)
    {
        $this->responseService = $responseService;
    }

    protected function response(mixed $data = null, int $statusCode = 200): ResponseInterface
    {
        return $this->responseService->createResponse($data, $statusCode);
    }
}
