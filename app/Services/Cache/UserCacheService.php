<?php

namespace App\Services\Cache;

use Illuminate\Support\Facades\Cache;

class UserCacheService
{
    public function getScope()
    {
        $authUser = auth()->user();

        if ($authUser->hasRole('super_admin')) {
            return [
                'tag' => 'superadmin:users',
                'company_id' => null
            ];
        }

        return [
            'tag' => "company:{$authUser->company_id}:users",
            'company_id' => $authUser->company_id
        ];
    }

    public function getUserCacheKey($userId)
    {
        $authUser = auth()->user();

        return $authUser->hasRole('super_admin')
            ? "superadmin:user:{$userId}"
            : "company:{$authUser->company_id}:user:{$userId}";
    }

    public function clearUserListCache($companyId)
    {
        Cache::tags(["company:{$companyId}:users"])->flush();
        Cache::tags(['superadmin:users'])->flush();
    }

    public function clearSingleUserCache($userId, $companyId)
    {
        Cache::forget("company:{$companyId}:user:{$userId}");
        Cache::forget("superadmin:user:{$userId}");
    }

    public function getUserListTTL()
    {
        return now()->addMinutes(3);
    }

    public function getUserTTL()
    {
        return now()->addMinutes(10);
    }
}