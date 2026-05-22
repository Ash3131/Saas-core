<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Services\UserService;
use App\Services\Cache\UserCacheService;
use App\Http\Controllers\API\BaseAPIController;

class UserController extends BaseAPIController
{
    protected $userService;
    protected $userCacheService;

    public function __construct(UserService $userService, UserCacheService $userCacheService)
    {
        $this->userService = $userService;
        $this->userCacheService = $userCacheService;
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
    public function notifications(Request $request)
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate($request->get('per_page', 10));

        $data = collect($notifications->items())->map(function ($n) {
            return [
                'id' => $n->id,
                'message' => $n->data['message'] ?? null,
                'user_id' => $n->data['user_id'] ?? null,
                'read' => !is_null($n->read_at),
                'created_at' => $n->created_at,
            ];
        });

        return $this->handleResponse([
            'status' => true,
            'message' => 'Notifications fetched',
            'data' => $data,
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'unread_count' => $request->user()->unreadNotifications()->count(),
            ],
            'code' => 200
        ]);
    }
    
    public function markAsReadAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return $this->handleResponse([
            'status' => true,
            'message' => 'Marked as read',
            'data' => null,
            'code' => 200
        ]);
    }

    public function cacheMetrics(Request $request)
    {
        $companyId = $request->user()->company_id;
    
        return $this->handleResponse([
            'status' => true,
            'message' => 'Cache metrics fetched successfully',
            'data' => $this->userCacheService->getUserCacheMetrics($companyId),
            'code' => 200
        ]);
    }
}