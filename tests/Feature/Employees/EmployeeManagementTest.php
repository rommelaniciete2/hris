<?php

namespace Tests\Feature\Employees;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_employee_index_requires_authentication()
    {
        $this->get(route('employees.index'))
            ->assertRedirect(route('login'));
    }

    public function test_employee_show_requires_authentication()
    {
        $this->get(route('employees.show', ['employee' => 1001]))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_the_employee_index_page()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('employees.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('employees/Index')
                ->has('stats', 4)
                ->where('filters.search', '')
                ->where('filters.status', '')
                ->has('employees', 4)
                ->where('employees.0.name', 'Mara Santos')
                ->where('employees.1.department', 'Finance'),
            );
    }

    public function test_authenticated_users_can_view_the_employee_detail_page()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('employees.show', ['employee' => 1001]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('employees/Show')
                ->where('employee.name', 'Mara Santos')
                ->where('employee.employeeNumber', 'EMP-1001')
                ->has('personalDetails', 7)
                ->has('jobDetails', 8)
                ->has('employmentHistory', 4)
                ->has('positionTracking', 2)
                ->has('documents.contracts', 2)
                ->has('documents.ids', 2),
            );
    }

    public function test_employee_index_can_be_filtered_by_search()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('employees.index', ['search' => 'Finance']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('employees/Index')
                ->where('filters.search', 'Finance')
                ->has('employees', 1)
                ->where('employees.0.name', 'Dianne Garcia')
                ->where('employees.0.department', 'Finance'),
            );
    }

    public function test_employee_index_can_be_filtered_by_status()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('employees.index', ['status' => 'Probationary']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('employees/Index')
                ->where('filters.status', 'Probationary')
                ->has('employees', 1)
                ->where('employees.0.name', 'Bea Mendoza')
                ->where('employees.0.status', 'Probationary'),
            );
    }
}
