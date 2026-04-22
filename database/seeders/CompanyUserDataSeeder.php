<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Company;
use App\Models\Role;

class CompanyUserDataSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('name', 'admin')->first();
        $userRole  = Role::where('name', 'user')->first();

        // 🔹 Company 1
        $company1 = Company::factory()->create([
            'name' => 'Alpha Corp'
        ]);

        $admin1 = User::factory()->create([
            'name' => 'Admin Alpha',
            'email' => 'admin1@test.com',
            'company_id' => $company1->id,
        ]);

        $admin1->roles()->syncWithoutDetaching([$adminRole->id]);

        User::factory(2)->create([
            'company_id' => $company1->id,
        ])->each(function ($user) use ($userRole) {
            $user->roles()->syncWithoutDetaching([$userRole->id]);
        });

        // 🔹 Company 2
        $company2 = Company::factory()->create([
            'name' => 'Beta Corp'
        ]);

        $admin2 = User::factory()->create([
            'name' => 'Admin Beta',
            'email' => 'admin2@test.com',
            'company_id' => $company2->id,
        ]);

        $admin2->roles()->syncWithoutDetaching([$adminRole->id]);

        User::factory(2)->create([
            'company_id' => $company2->id,
        ])->each(function ($user) use ($userRole) {
            $user->roles()->syncWithoutDetaching([$userRole->id]);
        });
    }
}