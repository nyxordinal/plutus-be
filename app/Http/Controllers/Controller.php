<?php

namespace App\Http\Controllers;

use App\Exceptions\EntityNotFoundException;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Laravel\Lumen\Routing\Controller as BaseController;
use Tymon\JWTAuth\Exceptions\JWTException;

class Controller extends BaseController
{
    function baseResponse($httpCode, $message, $isErrorResponse, $data = null)
    {
        $arr = array('message' => $message);
        if (!$isErrorResponse && $data != null) {
            $arr['data'] = $data;
        }
        return response()->json($arr, $httpCode);
    }

    public function successResponse($data = null, $message = 'Success')
    {
        return $this->baseResponse(Response::HTTP_OK, $message, false, $data);
    }

    public function successLoginResponse($data = null,  $authToken = '', $message = 'Success')
    {
        return response()
            ->json(
                ['message' => $message, 'data' => $data],
                Response::HTTP_OK,
                ['x-token' => $authToken]
            );
    }

    public function createdResponse($data = null, $message = 'Success created')
    {
        return $this->baseResponse(Response::HTTP_CREATED, $message, false, $data);
    }

    public function failedResponse($message = 'Failed', $httpCode)
    {
        return $this->baseResponse($httpCode, $message, true);
    }

    public function errorResponse(Exception $exception)
    {
        $httpCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        $errMessage = $exception->getMessage();

        if ($exception instanceof ValidationException) {
            $error = $exception->errors();
            if (is_array($error) && (count($error) > 0)) {
                $errMessage = reset($error)[0];
            } else {
                $errMessage = $error;
            }
            $httpCode = Response::HTTP_BAD_REQUEST;
        } elseif ($exception instanceof EntityNotFoundException) {
            $httpCode = Response::HTTP_BAD_REQUEST;
        } elseif (
            $exception instanceof JWTException ||
            $exception instanceof AuthorizationException
        )
            $httpCode = Response::HTTP_UNAUTHORIZED;

        return $this->baseResponse($httpCode, $errMessage, true);
    }
}
