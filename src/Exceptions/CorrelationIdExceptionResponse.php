<?php

namespace LaravelCorrelationId\Exceptions;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorrelationIdExceptionResponse
{
    public function addHeader(Request $request, Response $response): Response {
        $attribute = config(
            'correlation-id.request_attribute',
            'correlation_id'
        );

        $correlationId = $request->attributes->get($attribute);

        if(!is_string($correlationId) || $correlationId === ''){
            return $response;
        }

        $header = config('correlation-id.header', 'X-Correlation-ID');

        $response->headers->set($header, $correlationId);
        return $response;
    }
}
