<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Employee Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .employee-avatar { width: 44px; height: 44px; object-fit: cover; }
        .employee-initials { width: 44px; height: 44px; }
        .autocomplete-menu { max-height: 18rem; overflow-y: auto; z-index: 1080; }
    </style>
</head>
<body class="bg-light">
<div class="container-fluid px-3 px-lg-5 py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Employee Dashboard</h1>
            <p class="text-secondary mb-0">Manage employees and salary periods</p>
        </div>
        <a href="{{ route('employees.create') }}" class="btn btn-primary">Add employee</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h2 class="h5 mb-0">Search and filter employees</h2>
        </div>
        <div class="card-body">
            <form id="employeeSearchForm" method="GET" action="{{ route('employees.index') }}" class="row g-3">
                <div class="col-12 col-sm-6 col-xl-3">
                    <label for="monthFilter" class="form-label">Month</label>
                    <select id="monthFilter" name="month" class="form-select">
                        <option value="all" @selected($filters['month'] === 'all')>All</option>
                        @foreach ($availableMonths as $month)
                            <option value="{{ $month['value'] }}" @selected($filters['month'] === $month['value'])>
                                {{ $month['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <label for="branchFilter" class="form-label">Branch</label>
                    <select id="branchFilter" name="branch" class="form-select">
                        <option value="all" @selected($filters['branch'] === 'all')>All</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string) $filters['branch'] === (string) $branch->id)>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <label for="departmentFilter" class="form-label">Department</label>
                    <select id="departmentFilter" name="dept" class="form-select">
                        <option value="all" @selected($filters['dept'] === 'all')>All</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" @selected((string) $filters['dept'] === (string) $department->id)>
                                {{ $department->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <label for="jobFilter" class="form-label">Job</label>
                    <select id="jobFilter" name="job" class="form-select">
                        <option value="all" @selected($filters['job'] === 'all')>All</option>
                        @foreach ($jobTitles as $jobTitle)
                            <option value="{{ $jobTitle->id }}" @selected((string) $filters['job'] === (string) $jobTitle->id)>
                                {{ $jobTitle->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label for="nameFilter" class="form-label">Name</label>
                    <div class="position-relative">
                        <input type="search" id="nameFilter" name="name" value="{{ $filters['name'] }}"
                               class="form-control" placeholder="Search employee name"
                               autocomplete="off" role="combobox" aria-autocomplete="list"
                               aria-expanded="false" aria-controls="employeeSuggestions">
                        <div id="employeeSuggestions" class="list-group position-absolute top-100 start-0 w-100 shadow d-none autocomplete-menu"
                             role="listbox"></div>
                    </div>
                </div>
                <div class="col-12 col-md-6 d-flex align-items-end">
                    <div class="form-check mb-2">
                        <input type="checkbox" class="form-check-input" id="hasNoSalary" name="has_no_salary"
                               value="1" @checked($filters['has_no_salary'])>
                        <label class="form-check-label" for="hasNoSalary">has no salary yet</label>
                    </div>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
            <div id="searchStatus" class="small text-secondary mt-2" role="status" aria-live="polite"></div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th scope="col">Profile Image</th>
                        <th scope="col">Full Name</th>
                        <th scope="col">Branch</th>
                        <th scope="col">Department</th>
                        <th scope="col">Job Title</th>
                        <th scope="col">Salary Month</th>
                        <th scope="col" class="text-end">Monthly Salary</th>
                        <th scope="col" class="text-end">Actions</th>
                    </tr>
                    </thead>
                    <tbody id="employeeTableBody">
                    @forelse ($employees as $employee)
                        <tr>
                            <td>
                                @if ($employee->image_url)
                                    <img src="{{ $employee->image_url }}" alt="{{ $employee->full_name }}"
                                         class="employee-avatar rounded-circle">
                                @else
                                    <div class="employee-avatar employee-initials rounded-circle bg-secondary-subtle text-secondary
                                                d-flex align-items-center justify-content-center fw-semibold">
                                        {{ \Illuminate\Support\Str::of($employee->full_name)->explode(' ')->map(fn ($part) => \Illuminate\Support\Str::substr($part, 0, 1))->take(2)->implode('') }}
                                    </div>
                                @endif
                            </td>
                            <td class="fw-semibold">{{ $employee->full_name }}</td>
                            <td><span class="badge text-bg-primary">{{ $employee->branch->name }}</span></td>
                            <td><span class="badge text-bg-info">{{ $employee->department->name }}</span></td>
                            <td>{{ $employee->jobTitle->name }}</td>
                            <td>{{ $employee->salary_period ?: '—' }}</td>
                            <td class="text-end text-nowrap">
                                @if ($employee->salary_month === null || (float) $employee->salary_month <= 0)
                                    <span class="badge text-bg-secondary">No Salary Yet</span>
                                @else
                                    {{ number_format((float) $employee->salary_month, 2) }}
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('employees.edit', $employee) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form action="{{ route('employees.destroy', $employee) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Delete this employee? This cannot be undone.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-secondary py-4">No employees match these filters.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
                <small id="paginationSummary" class="text-secondary">
                    Showing {{ $employees->firstItem() ?? 0 }}–{{ $employees->lastItem() ?? 0 }} of {{ $employees->total() }}
                </small>
                <div id="employeePagination">{{ $employees->links('pagination::bootstrap-5') }}</div>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const form = document.getElementById('employeeSearchForm');
    const nameInput = document.getElementById('nameFilter');
    const suggestions = document.getElementById('employeeSuggestions');
    const tableBody = document.getElementById('employeeTableBody');
    const searchStatus = document.getElementById('searchStatus');
    const paginationSummary = document.getElementById('paginationSummary');
    const paginationContainer = document.getElementById('employeePagination');
    const searchUrl = @json(route('employees.search', [], false));
    const indexUrl = @json(route('employees.index', [], false));
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    let debounceTimer;
    let activeRequest;

    function element(tag, text, className) {
        const node = document.createElement(tag);
        if (text !== undefined) node.textContent = text;
        if (className) node.className = className;
        return node;
    }

    function currentParams(page = 1) {
        const params = new URLSearchParams(new FormData(form));
        if (page > 1) params.set('page', String(page));
        return params;
    }

    function pageUrl(page) {
        const params = currentParams(page);
        const query = params.toString();
        return new URL(query ? `${indexUrl}?${query}` : indexUrl, window.location.href);
    }

    function showSuggestions(items) {
        suggestions.replaceChildren();
        if (items.length === 0) {
            suggestions.classList.add('d-none');
            nameInput.setAttribute('aria-expanded', 'false');
            return;
        }

        items.forEach((item, index) => {
            const button = element('button', undefined, 'list-group-item list-group-item-action d-flex justify-content-between align-items-center');
            button.type = 'button';
            button.role = 'option';
            button.dataset.suggestion = item.label;
            button.id = `employee-suggestion-${index}`;
            button.append(element('span', item.label), element('small', item.type, 'text-secondary ms-3'));
            suggestions.append(button);
        });
        suggestions.classList.remove('d-none');
        nameInput.setAttribute('aria-expanded', 'true');
    }

    function initials(name) {
        return name.trim().split(/\s+/).slice(0, 2).map((part) => part.charAt(0)).join('').toUpperCase();
    }

    function salaryPeriodLabel(period) {
        const match = /^(\d{4})-(\d{2})$/.exec(period || '');
        if (!match) return period || '—';
        return new Date(Number(match[1]), Number(match[2]) - 1, 1)
            .toLocaleString(undefined, { month: 'short', year: 'numeric' });
    }

    function makeEmployeeRow(employee) {
        const row = document.createElement('tr');

        const imageCell = document.createElement('td');
        if (employee.image_url) {
            const image = element('img');
            image.src = employee.image_url;
            image.alt = employee.full_name;
            image.className = 'employee-avatar rounded-circle';
            imageCell.append(image);
        } else {
            imageCell.append(element('div', initials(employee.full_name),
                'employee-avatar employee-initials rounded-circle bg-secondary-subtle text-secondary d-flex align-items-center justify-content-center fw-semibold'));
        }

        const nameCell = element('td', employee.full_name, 'fw-semibold');
        const branchCell = document.createElement('td');
        branchCell.append(element('span', employee.branch, 'badge text-bg-primary'));
        const departmentCell = document.createElement('td');
        departmentCell.append(element('span', employee.department, 'badge text-bg-info'));
        const jobCell = element('td', employee.job_title);
        const periodCell = element('td', salaryPeriodLabel(employee.salary_period));

        const salaryCell = element('td', undefined, 'text-end text-nowrap');
        if (employee.salary_month === null || Number(employee.salary_month) <= 0) {
            salaryCell.append(element('span', 'No Salary Yet', 'badge text-bg-secondary'));
        } else {
            salaryCell.textContent = Number(employee.salary_month).toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        const actionsCell = element('td', undefined, 'text-end text-nowrap');
        const editLink = element('a', 'Edit', 'btn btn-sm btn-outline-primary');
        editLink.href = employee.edit_url;
        actionsCell.append(editLink);

        const deleteForm = document.createElement('form');
        deleteForm.action = employee.delete_url;
        deleteForm.method = 'POST';
        deleteForm.className = 'd-inline';
        deleteForm.addEventListener('submit', (event) => {
            if (!window.confirm('Delete this employee? This cannot be undone.')) event.preventDefault();
        });
        const token = element('input');
        token.type = 'hidden';
        token.name = '_token';
        token.value = csrfToken;
        const method = element('input');
        method.type = 'hidden';
        method.name = '_method';
        method.value = 'DELETE';
        const deleteButton = element('button', 'Delete', 'btn btn-sm btn-outline-danger');
        deleteButton.type = 'submit';
        deleteForm.append(token, method, deleteButton);
        actionsCell.append(deleteForm);

        row.append(imageCell, nameCell, branchCell, departmentCell, jobCell, periodCell, salaryCell, actionsCell);
        return row;
    }

    function renderEmployees(employees) {
        tableBody.replaceChildren();
        if (employees.length === 0) {
            const row = document.createElement('tr');
            const cell = element('td', 'No employees match these filters.', 'text-center text-secondary py-4');
            cell.colSpan = 8;
            row.append(cell);
            tableBody.append(row);
            return;
        }
        employees.forEach((employee) => tableBody.append(makeEmployeeRow(employee)));
    }

    function renderPagination(pagination) {
        paginationContainer.replaceChildren();
        const current = pagination.current_page;
        const last = pagination.last_page;
        if (last <= 1) return;

        const nav = element('nav');
        nav.setAttribute('aria-label', 'Employee pages');
        const list = element('ul', undefined, 'pagination mb-0');
        nav.append(list);

        function addPage(label, page, options = {}) {
            const item = element('li', undefined, `page-item${options.active ? ' active' : ''}${options.disabled ? ' disabled' : ''}`);
            const link = element('a', label, 'page-link');
            link.href = pageUrl(page);
            link.dataset.page = page;
            if (options.active) link.setAttribute('aria-current', 'page');
            if (options.disabled) link.setAttribute('aria-disabled', 'true');
            item.append(link);
            list.append(item);
        }

        addPage('Previous', Math.max(1, current - 1), { disabled: current === 1 });
        const start = Math.max(1, current - 2);
        const end = Math.min(last, current + 2);
        if (start > 1) {
            addPage('1', 1);
            if (start > 2) list.append(element('li', '…', 'page-item disabled'));
        }
        for (let page = start; page <= end; page++) {
            addPage(String(page), page, { active: page === current });
        }
        if (end < last) {
            if (end < last - 1) list.append(element('li', '…', 'page-item disabled'));
            addPage(String(last), last);
        }
        addPage('Next', Math.min(last, current + 1), { disabled: current === last });
        paginationContainer.append(nav);
    }

    async function loadResults(page = 1) {
        if (activeRequest) activeRequest.abort();
        activeRequest = new AbortController();
        const currentRequest = activeRequest;
        const params = currentParams(page);
        searchStatus.textContent = 'Searching…';

        try {
            const response = await fetch(`${searchUrl}?${params}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: currentRequest.signal
            });
            if (!response.ok) throw new Error(`Search failed with status ${response.status}`);
            const data = await response.json();

            renderEmployees(data.employees);
            showSuggestions(data.suggestions);
            paginationSummary.textContent = data.pagination.total === 0
                ? 'Showing 0 of 0'
                : `Showing ${data.pagination.from}–${data.pagination.to} of ${data.pagination.total}`;
            renderPagination(data.pagination);
            searchStatus.textContent = `${data.pagination.total} employee${data.pagination.total === 1 ? '' : 's'} found.`;
            window.history.replaceState({}, '', pageUrl(data.pagination.current_page));
        } catch (error) {
            if (error.name !== 'AbortError') {
                searchStatus.textContent = 'Search could not be completed. Please try again.';
            }
        } finally {
            if (activeRequest === currentRequest) activeRequest = null;
        }
    }

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        window.clearTimeout(debounceTimer);
        loadResults(1);
    });

    nameInput.addEventListener('input', () => {
        window.clearTimeout(debounceTimer);
        debounceTimer = window.setTimeout(() => loadResults(1), 250);
    });

    form.querySelectorAll('select, input[type="checkbox"]').forEach((input) => {
        input.addEventListener('change', () => loadResults(1));
    });

    suggestions.addEventListener('click', (event) => {
        const button = event.target.closest('button[data-suggestion]');
        if (!button) return;
        nameInput.value = button.dataset.suggestion;
        loadResults(1);
        nameInput.focus();
    });

    document.addEventListener('click', (event) => {
        if (!form.contains(event.target)) showSuggestions([]);
    });

    paginationContainer.addEventListener('click', (event) => {
        const link = event.target.closest('a.page-link');
        if (!link || link.closest('.disabled')) {
            if (link) event.preventDefault();
            return;
        }

        const url = new URL(link.href, window.location.href);
        if (url.pathname !== new URL(indexUrl, window.location.href).pathname) return;

        event.preventDefault();
        for (const [key, value] of url.searchParams) {
            const input = form.elements.namedItem(key);
            if (input && input.type !== 'checkbox') input.value = value;
            if (input && input.type === 'checkbox') input.checked = value === '1';
        }
        loadResults(Number(link.dataset.page || url.searchParams.get('page') || 1));
    });
})();
</script>
</body>
</html>
