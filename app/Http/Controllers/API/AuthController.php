<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController;
use Illuminate\Http\Request;
use App\Services\AuthService;

class AuthController extends BaseController
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
        ]);

        return $this->handleResponse(
            $this->authService->register($validated)
        );
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        return $this->handleResponse(
            $this->authService->login($validated)
        );
    }

    public function profile(Request $request)
    {
        return $this->handleResponse(
            $this->authService->profile($request->user())
        );
    }

    public function logout(Request $request)
    {
        return $this->handleResponse(
            $this->authService->logout($request->user())
        );
    }
}