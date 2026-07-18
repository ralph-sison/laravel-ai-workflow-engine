<?php

namespace App\Actions\Auth;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RegisterTenantAction
{
    public function execute(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $tenant = Tenant::create([
                'name' => $data['organization'],
                'slug' => Tenant::generateSlug($data['organization']),
            ]);

            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            // Set Spatie team context so role is scoped to this tenant
            setPermissionsTeamId($tenant->id);
            $user->assignRole('owner');

            $token = $user->createToken('auth_token')->plainTextToken;

            return compact('tenant', 'user', 'token');
        });
    }
}
