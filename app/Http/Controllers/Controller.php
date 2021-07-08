<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Response;
use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    function baseResponse($code, $message, $isErrorResponse, $data = null,  $error = null)
    {
        if ($isErrorResponse) {
            return response()->json(['code' => $code, 'message' => $message, 'error' => $error], $code);
        }
        return response()->json(['code' => $code, 'message' => $message, 'data' => $data], $code);
    }

    public function successResponse($data = null, $message = 'Success')
    {
        return $this->baseResponse(Response::HTTP_OK, $message, false, $data);
    }

    public function createdResponse($data = null, $message = 'Success created')
    {
        return $this->baseResponse(Response::HTTP_CREATED, $message, false, $data);
    }

    public function internalServerErrorResponse(Exception $exception, $message = 'Unexpected Error')
    {
        return $this->baseResponse(Response::HTTP_INTERNAL_SERVER_ERROR, $message, true, null, $exception->getMessage());
    }

    public function badRequestResponse($errors, $message = 'Bad Request')
    {
        return $this->baseResponse(Response::HTTP_BAD_REQUEST, $message, true, null, $errors);
    }

    public function unauthorizedResponse($message = 'Unauthorized')
    {
        return $this->baseResponse(Response::HTTP_UNAUTHORIZED, $message, true);
    }

    public function forbiddenResponse($message = 'Forbidden')
    {
        return $this->baseResponse(Response::HTTP_FORBIDDEN, $message, true);
    }

    public function notFoundResponse($message = 'Resource not found')
    {
        return $this->baseResponse(Response::HTTP_NOT_FOUND, $message, true);
    }

    public function conflictResponse($message = 'Conflict')
    {
        return $this->baseResponse(response::HTTP_CONFLICT, $message, true);
    }

    public function unprocessableEntityResponse($message = 'Unprocessable entity')
    {
        return $this->baseResponse(Response::HTTP_UNPROCESSABLE_ENTITY, $message, true);
    }
}
