<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RefreshUserListCacheJob implements ShouldQueue
{
    use Queueable;

    protected $filters;
    protected $companyId;
    protected $key;
    protected $lockKey;

    public function __construct($filters, $companyId, $key, $lockKey)
    {
        $this->filters = $filters;
        $this->companyId = $companyId;
        $this->key = $key;
        $this->lockKey = $lockKey;
    }

    public function handle()
    {
            $query = User::query();

            if ($this->companyId) {
                $query->where('company_id', $this->companyId);
            }

            if (!empty($this->filters['search'])) {
                $search = $this->filters['search'];

                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            if (!empty($this->filters['sort_by']) && !empty($this->filters['sort_order'])) {
                $query->orderBy(
                    $this->filters['sort_by'],
                    $this->filters['sort_order']
                );
            } else {
                $query->latest();
            }

            $perPage = $this->filters['per_page'] ?? 10;

            $data = $query->paginate($perPage);

            $result = [
                'data' => collect($data->items())
                    ->map(fn($u) => $u->toArray())
                    ->toArray(),

                'meta' => [
                    'current_page' => $data->currentPage(),
                    'last_page' => $data->lastPage(),
                    'per_page' => $data->perPage(),
                    'total' => $data->total(),
                ]
            ];

            Cache::put(
                $this->key,
                $result,
                now()->addMinutes(10)
            );
    }
}