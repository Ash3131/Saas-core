<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use App\Interfaces\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{
    
    public function create(array $data)
    {
        $user = new User();

        $user->fill($data);

        // assign company id
        if (isset($data['company_id'])) {
            $user->company_id = $data['company_id'];
        }

        // assign created by
        if (isset($data['created_by'])) {
            $user->created_by = $data['created_by'];
        }

        $user->save();

        Cache::forget("company:{$user->company_id}:users");

        return $user;
    }

    public function findByEmail(string $email)
    {
        return User::where('email', $email)->first();
    }

    public function getAll($filters)
    {
        $companyId = auth()->user()->company_id;

        $cacheKey = "company:{$companyId}:users:" . md5(json_encode($filters));

        return Cache::remember($cacheKey, 300, function () use ($filters) {

            $query = User::query();

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

            return $query->paginate($perPage);
        });
    }

    public function findById($id)
    {
        $companyId = auth()->user()->company_id;

        $cacheKey = "company:{$companyId}:user:{$id}";

        return Cache::remember($cacheKey, 600, function () use ($id) {
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

        Cache::forget("company:{$user->company_id}:user:{$user->id}");
        Cache::forget("company:{$user->company_id}:users");
    
        return $user;
    }

    public function delete($id)
    {
        $user = User::find($id);

        if (!$user) {
            return null;
        }

        $user->delete();

        Cache::forget("company:{$user->company_id}:user:{$user->id}");
        Cache::forget("company:{$user->company_id}:users");

        return true;
    }
}
