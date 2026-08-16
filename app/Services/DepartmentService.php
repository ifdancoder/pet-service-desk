<?php

namespace App\Services;

use App\DataTransferObjects\DepartmentData;
use App\Models\Department;
use Illuminate\Support\Str;

class DepartmentService
{
    public function create(DepartmentData $data): Department
    {
        return Department::create([
            'name' => $data->name,
            'slug' => Str::slug($data->name),
        ]);
    }

    public function update(Department $department, DepartmentData $data): Department
    {
        $department->update([
            'name' => $data->name,
            'slug' => Str::slug($data->name),
        ]);

        return $department;
    }

    public function delete(Department $department): void
    {
        $department->delete();
    }
}
