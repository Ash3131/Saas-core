<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Services\UserService;
use App\Http\Controllers\API\BaseAPIController;

class UserController extends BaseAPIController
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index(Request $request)
    {
        return $this->handleResponse(
            $this->userService->getUsers($request->all())
        );
    }

    public function show($id)
    {
        return $this->handleResponse(
            $this->userService->getUserById($id)
        );
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => "sometimes|email|unique:users,email,$id",
            'password' => 'sometimes|min:6',
        ]);

        return $this->handleResponse(
            $this->userService->updateUser($id, $validated)
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
        ]);
    
        return $this->handleResponse(
            $this->userService->createUser($validated)
        );
    }

    public function destroy($id)
    {
        return $this->handleResponse(
            $this->userService->deleteUser($id)
        );
    }
}