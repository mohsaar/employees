<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\JobTitle;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->validatedFilters($request);

        $employees = $this->filteredEmployeesQuery($filters)
            ->orderBy('full_name')
            ->paginate(10)
            ->withQueryString();

        $availableMonths = Employee::query()
            ->whereNotNull('salary_period')
            ->where('salary_period', '!=', '')
            ->distinct()
            ->orderByDesc('salary_period')
            ->pluck('salary_period')
            ->map(fn (string $period): array => [
                'value' => $period,
                'label' => $this->salaryPeriodLabel($period),
            ])
            ->values();

        return view('employees.index', [
            'employees' => $employees,
            'filters' => $filters,
            'branches' => Branch::orderBy('name')->get(['id', 'name']),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'jobTitles' => JobTitle::orderBy('name')->get(['id', 'name']),
            'availableMonths' => $availableMonths,
        ]);
    }

    public function liveSearch(Request $request): JsonResponse
    {
        $request->validate(['page' => ['nullable', 'integer', 'min:1']]);
        $filters = $this->validatedFilters($request);
        $employees = $this->filteredEmployeesQuery($filters)
            ->orderBy('full_name')
            ->paginate(10);

        return response()->json([
            'employees' => $employees->getCollection()->map(fn (Employee $employee): array => [
                'full_name' => $employee->full_name,
                'email' => $employee->email,
                'phone' => $employee->phone,
                'branch' => $employee->branch->name,
                'department' => $employee->department->name,
                'job_title' => $employee->jobTitle->name,
                'salary_month' => $employee->salary_month,
                'salary_period' => $employee->salary_period,
                'image_url' => $employee->image_url,
                'edit_url' => route('employees.edit', $employee, false),
                'delete_url' => route('employees.destroy', $employee, false),
            ])->values(),
            'suggestions' => $this->autocompleteSuggestions($filters['name']),
            'pagination' => [
                'current_page' => $employees->currentPage(),
                'last_page' => $employees->lastPage(),
                'total' => $employees->total(),
                'from' => $employees->firstItem(),
                'to' => $employees->lastItem(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('employees.create', [
            'branches' => Branch::orderBy('name')->get(['id', 'name']),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'jobTitles' => JobTitle::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $imagePath = null;

        try {
            // Upload first, so we can clean up if the DB write fails.
            if ($request->hasFile('image')) {
                $imagePath = $this->storeImage($request->file('image'));
            }

            DB::transaction(function () use ($data, $imagePath) {
                Employee::create([
                    'full_name' => $data['full_name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'salary_month' => $data['salary_month'] ?? null,
                    'salary_period' => $data['salary_period'] ?? null,
                    'image_path' => $imagePath,
                    'branch_id' => $this->resolveLookup(Branch::class, $data['branch'], $data['new_branch'] ?? null),
                    'department_id' => $this->resolveLookup(Department::class, $data['department'], $data['new_department'] ?? null),
                    'job_title_id' => $this->resolveLookup(JobTitle::class, $data['job_title'], $data['new_job_title'] ?? null),
                ]);
            });
        } catch (Throwable $e) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }
            report($e);

            return back()->withInput()->with('error', 'Could not save the employee. Please try again.');
        }

        return redirect()->route('employees.create')
            ->with('success', 'Employee added successfully.');
    }

    public function edit(Employee $employee): View
    {
        return view('employees.edit', [
            'employee' => $employee->load(['branch', 'department', 'jobTitle']),
            'branches' => Branch::orderBy('name')->get(['id', 'name']),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'jobTitles' => JobTitle::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $rules = [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('employees', 'email')->ignore($employee->id)],
            'phone' => ['required', 'string', 'regex:/^[0-9+\-\s()]{7,20}$/'],
            'salary_month' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'salary_period' => ['nullable', 'date_format:Y-m'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];

        foreach (['branch' => 'branches', 'department' => 'departments', 'job_title' => 'job_titles'] as $field => $table) {
            $rules[$field] = [
                'required',
                $request->input($field) === '__new__' ? 'in:__new__' : "exists:{$table},id",
            ];
            $rules["new_{$field}"] = ['required_if:'.$field.',__new__', 'nullable', 'string', 'max:100'];
        }

        $data = $request->validate($rules);
        $newImagePath = null;
        $oldImagePath = $employee->image_path;

        try {
            if ($request->hasFile('image')) {
                $newImagePath = $this->storeImage($request->file('image'));
            }

            DB::transaction(function () use ($data, $employee, $newImagePath): void {
                $employee->update([
                    'full_name' => $data['full_name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'salary_month' => $data['salary_month'] ?? null,
                    'salary_period' => $data['salary_period'] ?? null,
                    'image_path' => $newImagePath ?? $employee->image_path,
                    'branch_id' => $this->resolveLookup(Branch::class, $data['branch'], $data['new_branch'] ?? null),
                    'department_id' => $this->resolveLookup(Department::class, $data['department'], $data['new_department'] ?? null),
                    'job_title_id' => $this->resolveLookup(JobTitle::class, $data['job_title'], $data['new_job_title'] ?? null),
                ]);
            });
        } catch (Throwable $e) {
            if ($newImagePath) {
                Storage::disk('public')->delete($newImagePath);
            }
            report($e);

            return back()->withInput()->with('error', 'Could not update the employee. Please try again.');
        }

        $message = 'Employee updated successfully.';
        if ($newImagePath && $oldImagePath && ! Storage::disk('public')->delete($oldImagePath)) {
            report(new RuntimeException("Could not delete the previous employee image: {$oldImagePath}"));
            $message .= ' The previous profile image could not be removed.';
        }

        return redirect()->route('employees.index')->with('success', $message);
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $imagePath = $employee->image_path;

        DB::transaction(fn () => $employee->delete());

        if ($imagePath && ! Storage::disk('public')->delete($imagePath)) {
            report(new RuntimeException("Could not delete the employee image: {$imagePath}"));

            return redirect()->route('employees.index')
                ->with('error', 'Employee deleted, but the profile image could not be removed.');
        }

        return redirect()->route('employees.index')->with('success', 'Employee deleted successfully.');
    }

    /**
     * Smart select: returns the ID of an existing record, or creates the
     * new value (reusing a duplicate if the same name already exists).
     *
     * @param  class-string<Model>  $model
     */
    private function resolveLookup(string $model, string $selected, ?string $newName): int
    {
        if ($selected !== '__new__') {
            return (int) $selected;
        }

        $name = trim(preg_replace('/\s+/', ' ', (string) $newName));

        // MySQL's default collation is case-insensitive, so "cairo" matches "Cairo".
        return $model::firstOrCreate(['name' => $name])->id;
    }

    private function storeImage(UploadedFile $file): string
    {
        // Saves to storage/app/public/employees/<random-hash>.<ext>
        return $file->store('employees', 'public');
    }

    private function validatedFilters(Request $request): array
    {
        $validated = $request->validate([
            'month' => ['exclude_if:month,all', 'nullable', 'string', 'max:100'],
            'branch' => ['exclude_if:branch,all', 'nullable', 'integer', 'exists:branches,id'],
            'dept' => ['exclude_if:dept,all', 'nullable', 'integer', 'exists:departments,id'],
            'job' => ['exclude_if:job,all', 'nullable', 'integer', 'exists:job_titles,id'],
            'name' => ['nullable', 'string', 'max:255'],
            'has_no_salary' => ['nullable', 'boolean'],
        ]);

        return [
            'month' => $request->query('month', 'all'),
            'branch' => $request->query('branch', 'all'),
            'dept' => $request->query('dept', 'all'),
            'job' => $request->query('job', 'all'),
            'name' => trim($validated['name'] ?? ''),
            'has_no_salary' => $request->boolean('has_no_salary'),
        ];
    }

    private function filteredEmployeesQuery(array $filters): Builder
    {
        $query = Employee::query()->with(['branch', 'department', 'jobTitle']);

        if ($filters['month'] !== 'all' && $filters['month'] !== '') {
            $query->where('salary_period', $filters['month']);
        }
        if ($filters['branch'] !== 'all' && $filters['branch'] !== '') {
            $query->where('branch_id', $filters['branch']);
        }
        if ($filters['dept'] !== 'all' && $filters['dept'] !== '') {
            $query->where('department_id', $filters['dept']);
        }
        if ($filters['job'] !== 'all' && $filters['job'] !== '') {
            $query->where('job_title_id', $filters['job']);
        }

        $normalizedName = $this->normalizeSearch($filters['name']);
        if ($normalizedName !== '') {
            $pattern = "%{$normalizedName}%";
            $query->where(function (Builder $employees) use ($pattern): void {
                foreach (['full_name', 'email', 'phone', 'salary_month', 'salary_period'] as $column) {
                    $employees->orWhereRaw("LOWER(REPLACE(employees.{$column}, ' ', '')) LIKE ?", [$pattern]);
                }

                $employees
                    ->orWhereHas('branch', fn (Builder $branch) => $branch->whereRaw("LOWER(REPLACE(name, ' ', '')) LIKE ?", [$pattern]))
                    ->orWhereHas('department', fn (Builder $department) => $department->whereRaw("LOWER(REPLACE(name, ' ', '')) LIKE ?", [$pattern]))
                    ->orWhereHas('jobTitle', fn (Builder $jobTitle) => $jobTitle->whereRaw("LOWER(REPLACE(name, ' ', '')) LIKE ?", [$pattern]));
            });
        }

        if ($filters['has_no_salary']) {
            $query->where(function (Builder $query): void {
                $query->whereNull('salary_month')
                    ->orWhere('salary_month', 0)
                    ->orWhereNull('salary_period');
            });
        }

        return $query;
    }

    private function autocompleteSuggestions(string $search): array
    {
        $prefix = $this->normalizeSearch($search);
        if ($prefix === '') {
            return [];
        }

        $pattern = "{$prefix}%";
        $sources = [
            'Employee' => Employee::query()
                ->whereRaw("LOWER(REPLACE(full_name, ' ', '')) LIKE ?", [$pattern])
                ->orderBy('full_name')
                ->limit(4)
                ->pluck('full_name'),
            'Branch' => Branch::query()
                ->whereRaw("LOWER(REPLACE(name, ' ', '')) LIKE ?", [$pattern])
                ->orderBy('name')
                ->limit(4)
                ->pluck('name'),
            'Department' => Department::query()
                ->whereRaw("LOWER(REPLACE(name, ' ', '')) LIKE ?", [$pattern])
                ->orderBy('name')
                ->limit(4)
                ->pluck('name'),
            'Job title' => JobTitle::query()
                ->whereRaw("LOWER(REPLACE(name, ' ', '')) LIKE ?", [$pattern])
                ->orderBy('name')
                ->limit(4)
                ->pluck('name'),
        ];

        $suggestions = collect();
        foreach ($sources as $type => $labels) {
            foreach ($labels as $label) {
                $suggestions->push(['label' => $label, 'type' => $type]);
            }
        }

        return $suggestions
            ->unique(fn (array $suggestion): string => $this->normalizeSearch($suggestion['label']))
            ->take(8)
            ->values()
            ->all();
    }

    private function normalizeSearch(string $search): string
    {
        return mb_strtolower(str_replace(' ', '', trim($search)));
    }

    private function salaryPeriodLabel(string $period): string
    {
        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $period) === 1) {
            $date = DateTimeImmutable::createFromFormat('!Y-m', $period);

            if ($date !== false) {
                return $date->format('M Y');
            }
        }

        return $period;
    }
}
