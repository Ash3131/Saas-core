<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use App\Services\Cache\UserCacheService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;

class RefreshUserListCacheJob implements ShouldQueue
{
    use Queueable;

    protected $filters;
    protected $companyId;
    protected $tag;
    protected $key;
    protected $cache;
    protected $lockKey;

    public function __construct($filters, $companyId, $tag, $key, $lockKey)
    {
        $this->filters = $filters;
        $this->companyId = $companyId;
        $this->tag = $tag;
        $this->key = $key;
        $this->lockKey = $lockKey;
    }

    public function handle(UserCacheService $cache)
    {
        try {
            $query = User::query();

            if ($this->companyId) {
                $query->where('company_id', $this->companyId);
            }

            if (!empty($this->filters['search'])) {
                $search = $this->filters['search'];

                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%$search%")
                      ->orWhere('email', 'like', "%$search%");
                });
            }

            if (!empty($this->filters['sort_by']) && !empty($this->filters['sort_order'])) {
                $query->orderBy($this->filters['sort_by'], $this->filters['sort_order']);
            } else {
                $query->latest();
            }

            $perPage = $this->filters['per_page'] ?? 10;

            $data = $query->paginate($perPage);

            Cache::tags([$this->tag])->put($this->key, [
                'data' => collect($data->items())->map(fn($u) => $u->toArray())->toArray(),
                'meta' => [
                    'current_page' => $data->currentPage(),
                    'last_page' => $data->lastPage(),
                    'per_page' => $data->perPage(),
                    'total' => $data->total(),
                ]
            ], $cache->getUserListTTL());

        } finally {
            // always release lock
            Cache::forget($this->lockKey);
        }
    }
}