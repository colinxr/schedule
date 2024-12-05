<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Str;

class UserService
{
    public function __construct(
        private UserRepository $repository
    ) {}

    public function createClient(array $data): User
    {
        $existingClient = User::where('email', $data['email'])
            ->where('role', 'client')
            ->first();

        if ($existingClient) {
            return $existingClient;
        }

        return $this->repository->createClient([
            ...$data,
            'password' => bcrypt(Str::random(16))
        ]);
    }
} 