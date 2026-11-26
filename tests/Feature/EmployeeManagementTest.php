<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\JobTitle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_searches_related_fields_and_paginates_results(): void
    {
        $branch = Branch::create(['name' => 'North office']);
        $department = Department::create(['name' => 'Operations']);
        $jobTitle = JobTitle::create(['name' => 'Coordinator']);

        foreach (range(1, 11) as $number) {
            Employee::create([
                'full_name' => "Employee {$number}",
                'email' => "employee{$number}@example.com",
                'phone' => '1234567890',
                'branch_id' => $branch->id,
                'department_id' => $department->id,
                'job_title_id' => $jobTitle->id,
                'salary_month' => 4200,
            ]);
        }

        $this->get(route('employees.index', ['dept' => $department->id]))
            ->assertOk()
            ->assertSee('Employee 1')
            ->assertViewHas('employees', function ($employees): bool {
                return $employees->total() === 11
                    && $employees->count() === 10
                    && $employees->hasMorePages();
            });
    }

    public function test_live_search_is_case_and_space_insensitive_across_employee_fields(): void
    {
        [$employee] = $this->createEmployee();
        $employee->update([
            'full_name' => 'Mohamed Hassan',
            'email' => 'mohamed.hassan@example.com',
        ]);

        foreach ([
            'mohamedhassan',
            'MOHAMED HAS',
            'hamedhas',
            'MOHAMED.HASSAN@EXAMPLE.COM',
            '1234567890',
            '4000',
            'centraloffice',
            'engineering',
            'DEVELOPER',
        ] as $term) {
            $this->getJson(route('employees.search', ['name' => $term]))
                ->assertOk()
                ->assertJsonPath('employees.0.full_name', 'Mohamed Hassan');
        }

        $this->getJson(route('employees.search', ['name' => 'mohamed']))
            ->assertJsonPath('suggestions.0.label', 'Mohamed Hassan')
            ->assertJsonPath('suggestions.0.type', 'Employee');
    }

    public function test_salary_period_and_employee_filters_combine_with_no_salary_filter(): void
    {
        [$employee, $branch, $department, $jobTitle] = $this->createEmployee();
        $employee->update(['salary_month' => null, 'salary_period' => '2026-11']);

        $this->get(route('employees.index', [
            'month' => '2026-11',
            'branch' => $branch->id,
            'dept' => $department->id,
            'job' => $jobTitle->id,
            'name' => 'currentemployee',
            'has_no_salary' => '1',
        ]))
            ->assertOk()
            ->assertSee('Nov 2026')
            ->assertSee('No Salary Yet')
            ->assertViewHas('employees', fn ($employees): bool => $employees->total() === 1);
    }

    public function test_employee_without_salary_can_be_created(): void
    {
        $branch = Branch::create(['name' => 'Central office']);
        $department = Department::create(['name' => 'Engineering']);
        $jobTitle = JobTitle::create(['name' => 'Developer']);

        $this->post(route('employees.store'), [
            'full_name' => 'Unpaid Employee',
            'email' => 'unpaid@example.com',
            'phone' => '1234567890',
            'branch' => (string) $branch->id,
            'department' => (string) $department->id,
            'job_title' => (string) $jobTitle->id,
        ])->assertRedirect(route('employees.create'));

        $this->assertDatabaseHas('employees', [
            'email' => 'unpaid@example.com',
            'salary_month' => null,
            'salary_period' => null,
        ]);
    }

    public function test_employee_can_be_updated_with_same_email_and_new_lookup_value(): void
    {
        Storage::fake('public');
        [$employee, $branch, $department, $jobTitle] = $this->createEmployee();
        $oldImagePath = 'employees/current.jpg';
        Storage::disk('public')->put($oldImagePath, 'old profile image');
        $employee->update(['image_path' => $oldImagePath]);

        $this->put(route('employees.update', $employee), [
            'full_name' => 'Updated Employee',
            'email' => $employee->email,
            'phone' => '1234567890',
            'salary_month' => '5100.50',
            'salary_period' => '2026-11',
            'branch' => (string) $branch->id,
            'department' => '__new__',
            'new_department' => 'Compliance',
            'job_title' => (string) $jobTitle->id,
            'image' => UploadedFile::fake()->image('updated-profile.jpg'),
        ])->assertRedirect(route('employees.index'));

        $employee->refresh();
        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'full_name' => 'Updated Employee',
            'email' => $employee->email,
            'salary_month' => '5100.50',
            'salary_period' => '2026-11',
        ]);
        $this->assertNotSame($oldImagePath, $employee->image_path);
        Storage::disk('public')->assertMissing($oldImagePath);
        Storage::disk('public')->assertExists($employee->image_path);
        $this->assertDatabaseHas('departments', ['name' => 'Compliance']);
        $this->assertDatabaseMissing('departments', ['id' => $department->id, 'name' => 'Compliance']);
    }

    public function test_edit_page_is_prefilled_and_duplicate_emails_are_rejected(): void
    {
        [$employee] = $this->createEmployee();
        $otherEmployee = Employee::create([
            'full_name' => 'Other Employee',
            'email' => 'other@example.com',
            'phone' => '1234567890',
            'branch_id' => $employee->branch_id,
            'department_id' => $employee->department_id,
            'job_title_id' => $employee->job_title_id,
            'salary_month' => 4500,
        ]);

        $this->get(route('employees.edit', $employee))
            ->assertOk()
            ->assertSee($employee->full_name)
            ->assertSee($employee->email);

        $this->put(route('employees.update', $employee), [
            'full_name' => $employee->full_name,
            'email' => $otherEmployee->email,
            'phone' => $employee->phone,
            'salary_month' => $employee->salary_month,
            'branch' => (string) $employee->branch_id,
            'department' => (string) $employee->department_id,
            'job_title' => (string) $employee->job_title_id,
        ])->assertSessionHasErrors('email');
    }

    public function test_employee_can_be_deleted_with_the_profile_image(): void
    {
        Storage::fake('public');
        [$employee] = $this->createEmployee();
        $imagePath = 'employees/profile.jpg';
        Storage::disk('public')->put($imagePath, 'profile image');
        $employee->update(['image_path' => $imagePath]);

        $this->delete(route('employees.destroy', $employee))
            ->assertRedirect(route('employees.index'));

        $this->assertDatabaseMissing('employees', ['id' => $employee->id]);
        Storage::disk('public')->assertMissing($imagePath);
    }

    private function createEmployee(): array
    {
        $branch = Branch::create(['name' => 'Central office']);
        $department = Department::create(['name' => 'Engineering']);
        $jobTitle = JobTitle::create(['name' => 'Developer']);
        $employee = Employee::create([
            'full_name' => 'Current Employee',
            'email' => 'current@example.com',
            'phone' => '1234567890',
            'branch_id' => $branch->id,
            'department_id' => $department->id,
            'job_title_id' => $jobTitle->id,
            'salary_month' => 4000,
        ]);

        return [$employee, $branch, $department, $jobTitle];
    }
}
