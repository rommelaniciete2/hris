<?php

namespace App\Services;

use App\Repositories\EmployeeRepositoryInterface;

class EmployeeManagementService
{
    protected $repo;

    public function __construct(EmployeeRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    public function getAll()
    {
        return $this->repo->all();
    }

    public function getById($id)
    {
        return $this->repo->find($id);
    }

    public function create(array $data)
    {
        $data['email'] = strtolower($data['email']);

        return $this->repo->create($data);
    }

    public function update($id, array $data)
    {
        return $this->repo->update($id, $data);
    }

    public function delete($id)
    {
        return $this->repo->delete($id);
    }
}
