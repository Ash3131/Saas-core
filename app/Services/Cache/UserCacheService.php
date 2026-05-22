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

    public function generateUserListKey($filters, $companyId, $version)
    {
        ksort($filters);

        return md5(json_encode([
            ...$filters,
            'company_id' => $companyId,
            'version' => $version
        ]));
    }
    
    public function getUserListVersion($companyId)
    {
        $key = $companyId
            ? "company:{$companyId}:users:version"
            : "superadmin:users:version";

        $version = Cache::get($key);

        if ($version === null) {
            Cache::forever($key, 1);
            return 1;
        }

        return (int) $version;
    }

    public function incrementUserListVersion($companyId)
    {
        $key = $companyId
            ? "company:{$companyId}:users:version"
            : "superadmin:users:version";
    
        $version = Cache::get($key);
    
        if ($version === null) {
            Cache::forever($key, 2); // first update → version 2
        } else {
            Cache::put($key, (int)$version + 1);
        }
    }

    public function getUserCacheKey($userId)
    {
        $authUser = auth()->user();

        return $authUser->hasRole('super_admin')
            ? "superadmin:user:{$userId}"
            : "company:{$authUser->company_id}:user:{$userId}";
    }

    public function getUserListTTL()
    {
        return now()->addMinutes(3);
    }

    public function getUserTTL()
    {
        return now()->addMinutes(10);
    }

    public function getUserCacheMetrics($companyId = null)
    {
        $prefix = "metrics:users:" . ($companyId ?? 'all');
    
        $hit = (int) Cache::get("{$prefix}:hit", 0);
        $miss = (int) Cache::get("{$prefix}:miss", 0);
    
        $total = $hit + $miss;
    
        return [
            'hit' => $hit,
            'miss' => $miss,
            'total' => $total,
            'hit_ratio' => $total > 0 ? round(($hit / $total) * 100, 2) : 0,
        ];
    }
}