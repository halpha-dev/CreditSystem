<?php

namespace App\api;

use App\security\Nonce;
use App\security\PermissionPolicy;

class AuthMiddleware
{
    /**
     * authenticate of user
     */
    public static function authenticate(array $request): array
    {
        if (empty($request['user_id']) || empty($request['nonce'])) {
            return self::unauthorized('اطلاعات احراز هویت ناقص است');
        }

        $userId = (int) $request['user_id'];
        $nonce = (string) $request['nonce'];

        if (!Nonce::verify($userId, $nonce)) {
            return self::unauthorized('احراز هویت نامعتبر است');
        }

        return [
            'success' => true,
            'user_id' => $userId,
        ];
    }

    /**
     * Role of User
     */
    public static function authorize(array $request, string $requiredRole): array
    {
        $auth = self::authenticate($request);

        if (!$auth['success']) {
            return $auth;
        }

        $userId = $auth['user_id'];

        if (!PermissionPolicy::hasRole($userId, $requiredRole)) {
            return self::forbidden('دسترسی غیرمجاز');
        }

        return [
            'success' => true,
            'user_id' => $userId,
            'role' => $requiredRole,
        ];
    }

    /**
     * just credits Users
     */
    public static function user(array $request): array
    {
        return self::authorize($request, 'user');
    }

    /**
     * Just Merchant Users
     */
    public static function merchant(array $request): array
    {
        return self::authorize($request, 'merchant');
    }

    /**
     * Just Admin
     */
    public static function admin(array $request): array
    {
        return self::authorize($request, 'admin');
    }

    protected static function unauthorized(string $message): array
    {
        return [
            'success' => false,
            'status' => 401,
            'message' => $message,
        ];
    }

    protected static function forbidden(string $message): array
    {
        return [
            'success' => false,
            'status' => 403,
            'message' => $message,
        ];
    }
}