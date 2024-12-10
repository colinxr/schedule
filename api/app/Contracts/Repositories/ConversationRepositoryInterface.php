<?php

namespace App\Contracts\Repositories;

interface ConversationRepositoryInterface
{
    public function create(array $data);
    public function find(int $id);
} 