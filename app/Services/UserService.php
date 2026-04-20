<?php

namespace App\Services;

use Illuminate\Support\Facades\Hash;
use App\Interfaces\UserRepositoryInterface;

class UserService
{
    protected $userRepo;

    public function __construct(UserRepositoryInterface $userRepo)
    {
        $this->userRepo = $userRepo;
    }

    public function getUsers($filters)
    {
        $users = $this->userRepo->getAll($filters);
    
        return [
            'status' => true,
            'message' => 'Users fetched successfully',
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
            'code' => 200
        ];
    }

    public function getUserById($id)
    {
        $user = $this->userRepo->findById($id);

        if (!$user) {
            return [
                'status' => false,
                'message' => 'User not found',
                'code' => 404
            ];
        }

        return [
            'status' => true,
            'message' => 'User fetched successfully',
            'data' => $user,
            'code' => 200
        ];
    }

    public function updateUser($id, $data)
    {
        $user = $this->userRepo->findById($id);

        if (!$user) {
            return [
                'status' => false,
                'message' => 'User not found',
                'code' => 404
            ];
        }

        // Hash password if present
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $updatedUser = $this->userRepo->update($id, $data);

        return [
            'status' => true,
            'message' => 'User updated successfully',
            'data' => $updatedUser,
            'code' => 200
        ];
    }

    public function createUser($data)
    {
        $data['password'] = Hash::make($data['password']);
    
        $user = $this->userRepo->create($data);
    
        return [
            'status' => true,
            'message' => 'User created successfully',
            'data' => $user,
            'code' => 201
        ];
    }

    public function deleteUser($id)
    {
        $user = $this->userRepo->findById($id);
    
        if (!$user) {
            return [
                'status' => false,
                'message' => 'User not found',
                'code' => 404
            ];
        }
    
        $this->userRepo->delete($id);
    
        return [
            'status' => true,
            'message' => 'User deleted successfully',
            'data' => null,
            'code' => 200
        ];
    }
}
