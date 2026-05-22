<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\ActivityLog;
use App\Models\Scopes\CompanyScope;
use Illuminate\Support\Facades\Hash;
use App\Interfaces\UserRepositoryInterface;
use App\Notifications\UserCreatedNotification;

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
            'company_id' => auth()->user()?->company_id,
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
            'data' => $users['data'],
            'meta' => $users['meta'],
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
    
        // Get user WITHOUT scope for validation
        $user = User::withoutGlobalScope(CompanyScope::class)->find($id);
    
        if (!$user) {
            return [
                'status' => false,
                'message' => 'User not found',
                'code' => 404
            ];
        }
    
        if ($this->isUnauthorizedTenant($user)) {
            return [
                'status' => false,
                'message' => 'Unauthorized access',
                'code' => 403
            ];
        }
    
        $hidden = ['password', 'remember_token', 'updated_at', 'created_at'];
    
        $oldData = collect($user->toArray())->except($hidden)->toArray();
    
        // Hash password if present
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
    
        $updatedUser = $this->userRepo->update($id, $data);
    
        // Get fresh data AFTER update
        $newData = collect($updatedUser->fresh()->toArray())->except($hidden)->toArray();
    
        $changedNew = array_diff_assoc($newData, $oldData);
    
        if (empty($changedNew)) {
            return [
                'status' => true,
                'message' => 'User updated successfully',
                'data' => $updatedUser,
                'code' => 200
            ];
        }
    
        $changedOld = array_intersect_key($oldData, $changedNew);
    
        $this->logActivity(
            'updated',
            $updatedUser,
            $changedOld,
            $changedNew
        );
    
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

        $admin = auth()->user();

        if ($admin) {
            $admin->notify(new UserCreatedNotification($user));
        }

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

        if ($this->isUnauthorizedTenant($user)) {
            return [
                'status' => false,
                'message' => 'Unauthorized access',
                'code' => 403
            ];
        }

        $oldData = collect($user->toArray())
            ->except(['password', 'remember_token', 'updated_at', 'created_at'])
            ->toArray();

        $deleted = $this->userRepo->delete($id);

        if (!$deleted) {
            return [
                'status' => false,
                'message' => 'Delete failed',
                'code' => 500
            ];
        }

        $this->logActivity('deleted', $user, $oldData, null);

        return [
            'status' => true,
            'message' => 'User deleted successfully',
            'data' => null,
            'code' => 200
        ];
    }
}
