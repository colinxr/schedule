<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    public function createClient(array $data): User
    {
        return User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => 'client',
        ]);
    }
} 