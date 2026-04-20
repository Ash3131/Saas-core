<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

use App\Interfaces\UserRepositoryInterface;

class AuthService
{
    protected $userRepo;

    public function __construct(UserRepositoryInterface $userRepo)
    {
        $this->userRepo = $userRepo;
    }

    public function register($data)
    {
        $data['password'] = Hash::make($data['password']);

        $user = $this->userRepo->create($data);

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'status' => true,
            'message' => 'Registration successful',
            'data' => [
                'user' => $user,
                'token' => $token
            ],
            'code' => 201
        ];
    }

    public function login($data)
    {
        $user = $this->userRepo->findByEmail($data['email']);

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return [
                'status' => false,
                'message' => 'Invalid credentials',
                'code' => 401
            ];
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'status' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => $user,
                'token' => $token
            ],
            'code' => 200
        ];
    }

    public function profile($user)
    {
        if (!$user) {
            return [
                'status' => false,
                'message' => 'User not found',
                'code' => 404
            ];
        }

        return [
            'status' => true,
            'message' => 'Profile fetched successfully',
            'data' => $user,
            'code' => 200
        ];
    }

    public function logout($user)
    {
        if (!$user) {
            return [
                'status' => false,
                'message' => 'User not found',
                'code' => 404
            ];
        }
    
        $user->tokens()->delete();
    
        return [
            'status' => true,
            'message' => 'Logged out successfully',
            'data' => null,
            'code' => 200
        ];
    }
}