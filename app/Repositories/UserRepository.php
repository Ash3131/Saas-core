<?php

namespace App\Repositories;

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

        $this->cache->clearUserListCache($user->company_id);

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

        $key = md5(json_encode([
            ...$filters,
            'company_id' => $companyId
        ]));

        $cached = Cache::tags([$tag])->get($key);

        if ($cached) {

            $lockKey = "refresh_lock:{$key}";

            if (Cache::add($lockKey, true, 30)) {
                dispatch(new RefreshUserListCacheJob(
                    $filters,
                    $companyId,
                    $tag,
                    $key,
                    $lockKey
                ));
            }
        
            return $cached;
        }

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

        Cache::tags([$tag])->put($key, $result, $this->cache->getUserListTTL());

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

        if (!$user) {
            return null;
        }

        $user->update($data);

        $this->cache->clearSingleUserCache($user->id, $user->company_id);
        $this->cache->clearUserListCache($user->company_id);

        return $user;
    }

    public function delete($id)
    {
        $user = User::find($id);

        if (!$user) {
            return null;
        }

        $companyId = $user->company_id;

        $user->delete();

        $this->cache->clearSingleUserCache($id, $companyId);
        $this->cache->clearUserListCache($companyId);

        return true;
    }
}