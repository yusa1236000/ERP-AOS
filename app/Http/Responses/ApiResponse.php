<?php
// app/Http/Responses/ApiResponse.php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ApiResponse
{
    /**
     * Return a success response
     */
    public static function success($data = null, string $message = 'Success', int $status = Response::HTTP_OK): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $status);
    }

    /**
     * Return an error response
     */
    public static function error(string $message = 'Error', $errors = null, int $status = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }

    /**
     * Return a forbidden response
     */
    public static function forbidden(string $message = 'Access denied', array $details = []): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
            'error_code' => 'ACCESS_DENIED',
        ];

        if (!empty($details)) {
            $response['details'] = $details;
        }

        return response()->json($response, Response::HTTP_FORBIDDEN);
    }

    /**
     * Return an unauthorized response
     */
    public static function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error_code' => 'UNAUTHORIZED',
        ], Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Return a not found response
     */
    public static function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error_code' => 'NOT_FOUND',
        ], Response::HTTP_NOT_FOUND);
    }

    /**
     * Return a validation error response
     */
    public static function validationError(string $message = 'Validation failed', array $errors = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error_code' => 'VALIDATION_ERROR',
            'errors' => $errors,
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * Return a server error response
     */
    public static function serverError(string $message = 'Internal server error'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error_code' => 'SERVER_ERROR',
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Return a permission denied response
     */
    public static function permissionDenied(string $message = 'Permission denied', array $requiredPermissions = [], array $userPermissions = []): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
            'error_code' => 'PERMISSION_DENIED',
        ];

        if (!empty($requiredPermissions)) {
            $response['required_permissions'] = $requiredPermissions;
        }

        if (!empty($userPermissions)) {
            $response['user_permissions'] = $userPermissions;
        }

        return response()->json($response, Response::HTTP_FORBIDDEN);
    }

    /**
     * Return a role denied response
     */
    public static function roleDenied(string $message = 'Role access denied', array $requiredRoles = [], array $userRoles = []): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
            'error_code' => 'ROLE_DENIED',
        ];

        if (!empty($requiredRoles)) {
            $response['required_roles'] = $requiredRoles;
        }

        if (!empty($userRoles)) {
            $response['user_roles'] = $userRoles;
        }

        return response()->json($response, Response::HTTP_FORBIDDEN);
    }

    /**
     * Return a module access denied response
     */
    public static function moduleAccessDenied(string $module, string $action = 'access', string $message = null): JsonResponse
    {
        $message = $message ?? "Access denied to {$module} module for {$action} action";

        return response()->json([
            'success' => false,
            'message' => $message,
            'error_code' => 'MODULE_ACCESS_DENIED',
            'module' => $module,
            'action' => $action,
        ], Response::HTTP_FORBIDDEN);
    }

    /**
     * Return a paginated response
     */
    public static function paginated($data, string $message = 'Data retrieved successfully'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data->items(),
            'pagination' => [
                'current_page' => $data->currentPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
                'last_page' => $data->lastPage(),
                'from' => $data->firstItem(),
                'to' => $data->lastItem(),
                'has_more_pages' => $data->hasMorePages(),
            ],
        ]);
    }

    /**
     * Return a collection response
     */
    public static function collection($data, string $message = 'Data retrieved successfully'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'count' => is_countable($data) ? count($data) : 0,
        ]);
    }

    /**
     * Return a resource created response
     */
    public static function created($data, string $message = 'Resource created successfully'): JsonResponse
    {
        return self::success($data, $message, Response::HTTP_CREATED);
    }

    /**
     * Return a resource updated response
     */
    public static function updated($data, string $message = 'Resource updated successfully'): JsonResponse
    {
        return self::success($data, $message);
    }

    /**
     * Return a resource deleted response
     */
    public static function deleted(string $message = 'Resource deleted successfully'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }

    /**
     * Return a custom status response
     */
    public static function custom($data = null, string $message = 'Success', int $status = Response::HTTP_OK, array $headers = []): JsonResponse
    {
        $response = [
            'success' => $status >= 200 && $status < 300,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $status, $headers);
    }

    /**
     * Return response with user context
     */
    public static function withUserContext($data = null, string $message = 'Success', int $status = Response::HTTP_OK): JsonResponse
    {
        $authService = app(\App\Services\AuthService::class);

        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        // Add user context if authenticated
        if ($authService->check()) {
            $response['user_context'] = [
                'user_id' => $authService->user()->id,
                'roles' => $authService->getUserRoles(),
                'accessible_modules' => array_keys($authService->getAccessibleModules()),
            ];
        }

        return response()->json($response, $status);
    }

    /**
     * Return debug response (only in debug mode)
     */
    public static function debug($data, $debugData = null, string $message = 'Debug response'): JsonResponse
    {
        if (!config('app.debug')) {
            return self::success($data, $message);
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'debug' => $debugData,
            'timestamp' => now()->toISOString(),
            'memory_usage' => memory_get_usage(true),
        ]);
    }
}

// Trait for controllers to use ApiResponse easily
namespace App\Http\Controllers\Traits;

use App\Http\Responses\ApiResponse;

trait ApiResponseTrait
{
    protected function successResponse($data = null, string $message = 'Success', int $status = 200)
    {
        return ApiResponse::success($data, $message, $status);
    }

    protected function errorResponse(string $message = 'Error', $errors = null, int $status = 400)
    {
        return ApiResponse::error($message, $errors, $status);
    }

    protected function forbiddenResponse(string $message = 'Access denied', array $details = [])
    {
        return ApiResponse::forbidden($message, $details);
    }

    protected function unauthorizedResponse(string $message = 'Unauthorized')
    {
        return ApiResponse::unauthorized($message);
    }

    protected function notFoundResponse(string $message = 'Resource not found')
    {
        return ApiResponse::notFound($message);
    }

    protected function validationErrorResponse(string $message = 'Validation failed', array $errors = [])
    {
        return ApiResponse::validationError($message, $errors);
    }

    protected function permissionDeniedResponse(string $message = 'Permission denied', array $requiredPermissions = [], array $userPermissions = [])
    {
        return ApiResponse::permissionDenied($message, $requiredPermissions, $userPermissions);
    }

    protected function createdResponse($data, string $message = 'Resource created successfully')
    {
        return ApiResponse::created($data, $message);
    }

    protected function updatedResponse($data, string $message = 'Resource updated successfully')
    {
        return ApiResponse::updated($data, $message);
    }

    protected function deletedResponse(string $message = 'Resource deleted successfully')
    {
        return ApiResponse::deleted($message);
    }

    protected function paginatedResponse($data, string $message = 'Data retrieved successfully')
    {
        return ApiResponse::paginated($data, $message);
    }

    protected function collectionResponse($data, string $message = 'Data retrieved successfully')
    {
        return ApiResponse::collection($data, $message);
    }
}
