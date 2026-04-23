<?php

namespace App\Services;

use App\Models\User;
use App\Models\ActivityLog;
use App\Models\Scopes\CompanyScope;
use Illuminate\Support\Facades\Hash;
use App\Interfaces\UserRepositoryInterface;

class UserService
{
    protected $userRepo;

    public function __construct(UserRepositoryInterface $userRepo)
    {
        $this->userRepo = $userRepo;
    }

    private function logActivity($action, $model, $old = null, $new = null)
    {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => get_class($model),
            'subject_id' => $model->id,
            'old_values' => $old,
            'new_values' => $new,
        ]);
    }

    private function allowedUserUpdateData(array $data): array
    {
        // Only these fields are updatable
        $allowed = ['name', 'email', 'password'];

        return array_intersect_key($data, array_flip($allowed));
    }

    private function isSuperAdmin(): bool
    {
        $user = auth()->user();
        return $user && $user->hasRole('super_admin');
    }

    private function isUnauthorizedTenant($user)
    {

        return !$this->isSuperAdmin() &&
               $user->company_id !== auth()->user()->company_id;
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
        $user = User::withoutGlobalScope(CompanyScope::class)->find($id);
    
        if (!$user) {
            return [
                'status' => false,
                'message' => 'User not found',
                'code' => 404
            ];
        }
    
        // Tenant check
        if ($this->isUnauthorizedTenant($user)) {
            
            return [
                'status' => false,
                'message' => 'Unauthorized access',
                'code' => 403
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
        $data = $this->allowedUserUpdateData($data);

        $user = User::withoutGlobalScope(CompanyScope::class)->find($id);

        if (!$user) {
            return [
                'status' => false,
                'message' => 'User not found',
                'code' => 404
            ];
        }

        // Tenant check
        if ($this->isUnauthorizedTenant($user)) {
            return [
                'status' => false,
                'message' => 'Unauthorized access',
                'code' => 403
            ];
        }

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        $this->logActivity(
            'updated',
            $user,
            $oldData,
            $user->fresh()->toArray()
        );

        return [
            'status' => true,
            'message' => 'User updated successfully',
            'data' => $user,
            'code' => 200
        ];
    }

    public function createUser($data)
    {
        $data['password'] = Hash::make($data['password']);
    
        $user = $this->userRepo->create($data);

        $this->logActivity(
            'created',
            $user,
            null,
            $user->toArray()
        );
    
        return [
            'status' => true,
            'message' => 'User created successfully',
            'data' => $user,
            'code' => 201
        ];
    }

    public function deleteUser($id)
    {
        $user = User::withoutGlobalScope(CompanyScope::class)->find($id);

        if (!$user) {
            return [
                'status' => false,
                'message' => 'User not found',
                'code' => 404
            ];
        }

        // Tenant check
        if ($this->isUnauthorizedTenant($user)) {

            return [
                'status' => false,
                'message' => 'Unauthorized access',
                'code' => 403
            ];
        }
    
        $user->delete();

        $this->logActivity(
            'deleted',
            $user,
            $oldData,
            null
        );
    
        return [
            'status' => true,
            'message' => 'User deleted successfully',
            'data' => null,
            'code' => 200
        ];
    }
}
