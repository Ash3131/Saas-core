<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Jobs\RefreshUserListCacheJob;
use App\Services\Cache\UserCacheService;
use Illuminate\Support\Facades\Cache;
use App\Interfaces\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{
    protected $cache;

    public function __construct(UserCacheService $cache)
    {
        $this->cache = $cache;
    }

    // public function warmUserListCache($companyId)
    // {
    //     $filters = [
    //         'per_page' => 10,
    //         'page' => 1
    //     ];

    //     // Correct tag handling (no auth dependency)
    //     $tag = $companyId
    //         ? "company:{$companyId}:users"
    //         : "superadmin:users";

    //     $version = $this->cache->getUserListVersion($companyId);

    //     $key = $this->cache->generateUserListKey($filters, $companyId, $version);

    //     $lockKey = "refresh_lock:{$key}";

    //     // Prevent duplicate jobs
    //     if (Cache::add($lockKey, true, 30)) {
    //         dispatch_sync(new RefreshUserListCacheJob(
    //             $filters,
    //             $companyId,
    //             $tag,
    //             $key,
    //             $lockKey
    //         ));
    //     }
    // }

    public function create(array $data)
    {
        $user = new User();

        $user->fill($data);

        if (isset($data['company_id'])) {
            $user->company_id = $data['company_id'];
        }

        if (isset($data['created_by'])) {
            $user->created_by = $data['created_by'];
        }

        $user->save();

        // Increment both scopes
        $this->cache->incrementUserListVersion($user->company_id);
        $this->cache->incrementUserListVersion(null);

        // Warm both
        // $this->warmUserListCache($user->company_id);
        // $this->warmUserListCache(null);

        return $user;
    }

    public function findByEmail(string $email)
    {
        return User::where('email', $email)->first();
    }

    public function getAll($filters)
    {
        $scope = $this->cache->getScope();

        $tag = $scope['tag'];
        $companyId = $scope['company_id'];

        // Normalize filters
        $filters['page'] = $filters['page'] ?? request('page', 1);
        ksort($filters);

        $version = $this->cache->getUserListVersion($companyId);

        $key = $this->cache->generateUserListKey($filters, $companyId, $version);

        Log::info('VERSION: ' . $version);
        Log::info('KEY: ' . $key);

        $prefix = "metrics:users:" . ($companyId ?? 'all');
        
        $cached = Cache::get($key);

        if ($cached) {
            Cache::increment("{$prefix}:hit");
                
            $lockKey = "refresh_lock:{$key}";
                
            if (Cache::add($lockKey, true, 30)) {
                dispatch(new RefreshUserListCacheJob(
                    $filters,
                    $companyId,
                    $key,
                    $lockKey
                ));
            }
        
            return $cached;
        }

        Cache::increment("{$prefix}:miss");

        $query = User::query();

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%");
            });
        }

        if (!empty($filters['sort_by']) && !empty($filters['sort_order'])) {
            $query->orderBy($filters['sort_by'], $filters['sort_order']);
        } else {
            $query->latest();
        }

        $perPage = $filters['per_page'] ?? 10;

        $data = $query->paginate($perPage);

        $result = [
            'data' => collect($data->items())->map(fn($u) => $u->toArray())->toArray(),
            'meta' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
            ]
        ];

        Cache::put($key, $result, $this->cache->getUserListTTL());

        return $result;
    }

    public function findById($id)
    {
        $cacheKey = $this->cache->getUserCacheKey($id);

        return Cache::remember($cacheKey, $this->cache->getUserTTL(), function () use ($id) {
            return User::find($id);
        });
    }

    public function update($id, $data)
    {
        $user = User::find($id);
        Log::info('INCREMENT VERSION for company: ' . $user->company_id);


        if (!$user) {
            return null;
        }

        $user->update($data);

        Cache::put(
            $this->cache->getUserCacheKey($user->id),
            $user->fresh(),
            $this->cache->getUserTTL()
        );

        // Increment both scopes
        $this->cache->incrementUserListVersion($user->company_id);
        $this->cache->incrementUserListVersion(null);

        // Warm both
        // $this->warmUserListCache($user->company_id);
        // $this->warmUserListCache(null);

        return $user;
    }

    public function delete($id)
    {
        $user = User::find($id);

        if (!$user) {
            return null;
        }

        $companyId = $user->company_id;

        Cache::forget($this->cache->getUserCacheKey($user->id));

        $user->delete();

        // Increment both scopes
        $this->cache->incrementUserListVersion($companyId);
        $this->cache->incrementUserListVersion(null);

        // Warm both
        // $this->warmUserListCache($companyId);
        // $this->warmUserListCache(null);

        return true;
    }
}