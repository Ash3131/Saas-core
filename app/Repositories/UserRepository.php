<?php

namespace App\Repositories;

use App\Models\User;
use App\Interfaces\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{
    
    public function create(array $data)
    {
        return User::create($data);
    }

    public function findByEmail(string $email)
    {
        return User::where('email', $email)->first();
    }

    public function getAll($filters)
    {
        $query = User::query();

        // Search (name/email)
        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%");
            });
        }

        // Sorting
        if (!empty($filters['sort_by']) && !empty($filters['sort_order'])) {
            $query->orderBy($filters['sort_by'], $filters['sort_order']);
        } else {
            $query->latest(); // default
        }

        // Pagination
        $perPage = $filters['per_page'] ?? 10;

        return $query->paginate($perPage);
    }

    public function findById($id)
    {
        return User::find($id);
    }

    public function update($id, $data)
    {
        $user = User::find($id);
        
        if (!$user) {
            return null;
        }
    
        $user->update($data);
    
        return $user;
    }

    public function delete($id)
    {
        $user = User::find($id);

        if (!$user) {
            return null;
        }

        $user->delete();

        return true;
    }
}
