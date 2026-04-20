<?php

namespace App\Interfaces;

interface UserRepositoryInterface
{
    public function create(array $data);
    public function findByEmail(string $email);
    public function getAll(array $filters);
    public function findById($id);
    public function update($id, array $data);
    public function delete($id);
}
