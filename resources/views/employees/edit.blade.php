<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Edit Employee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .avatar-preview { width: 100px; height: 100px; object-fit: cover; border-radius: 50%; background: #e9ecef; }
    </style>
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-9 col-xl-8">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h1 class="h3 mb-0">Edit employee</h1>
                <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary">Back to dashboard</a>
            </div>

            <div class="card shadow-sm">
                <div class="card-body p-4">
                    @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-danger">Please fix the highlighted fields below.</div>
                    @endif

                    <form action="{{ route('employees.update', $employee) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="d-flex align-items-center gap-3 mb-4">
                            <img id="imagePreview" class="avatar-preview"
                                 src="{{ $employee->image_url ?? 'data:image/svg+xml;charset=UTF-8,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22/%3E' }}"
                                 alt="Profile image preview">
                            <div class="flex-grow-1">
                                <label for="image" class="form-label">Profile image</label>
                                <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.webp"
                                       class="form-control @error('image') is-invalid @enderror">
                                <div class="form-text">Optional. JPG, PNG or WEBP, max 2 MB.</div>
                                @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="full_name" class="form-label">Full name</label>
                                <input type="text" id="full_name" name="full_name"
                                       value="{{ old('full_name', $employee->full_name) }}"
                                       class="form-control @error('full_name') is-invalid @enderror" required>
                                @error('full_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" id="email" name="email" value="{{ old('email', $employee->email) }}"
                                       class="form-control @error('email') is-invalid @enderror" required>
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" id="phone" name="phone" value="{{ old('phone', $employee->phone) }}"
                                       class="form-control @error('phone') is-invalid @enderror" required>
                                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="salary_month" class="form-label">Monthly salary</label>
                                <input type="number" step="0.01" min="0" id="salary_month" name="salary_month"
                                       value="{{ old('salary_month', $employee->salary_month) }}"
                                       class="form-control @error('salary_month') is-invalid @enderror">
                                @error('salary_month') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="salary_period" class="form-label">Salary month</label>
                                <input type="month" id="salary_period" name="salary_period"
                                       value="{{ old('salary_period', $employee->salary_period) }}"
                                       class="form-control @error('salary_period') is-invalid @enderror">
                                <div class="form-text">Leave salary and month blank if no salary is assigned yet.</div>
                                @error('salary_period') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            @php
                                $smartFields = [
                                    ['name' => 'branch', 'label' => 'Branch', 'items' => $branches, 'selected' => $employee->branch_id],
                                    ['name' => 'department', 'label' => 'Department', 'items' => $departments, 'selected' => $employee->department_id],
                                    ['name' => 'job_title', 'label' => 'Job title', 'items' => $jobTitles, 'selected' => $employee->job_title_id],
                                ];
                            @endphp

                            @foreach ($smartFields as $field)
                                @php
                                    $name = $field['name'];
                                    $selected = old($name, (string) $field['selected']);
                                @endphp
                                <div class="col-12 col-md-4 smart-select">
                                    <label for="{{ $name }}" class="form-label">{{ $field['label'] }}</label>
                                    <select id="{{ $name }}" name="{{ $name }}"
                                            class="form-select @error($name) is-invalid @enderror" required>
                                        @foreach ($field['items'] as $item)
                                            <option value="{{ $item->id }}" @selected((string) $selected === (string) $item->id)>
                                                {{ $item->name }}
                                            </option>
                                        @endforeach
                                        <option value="__new__" @selected($selected === '__new__')>+ Add new…</option>
                                    </select>
                                    @error($name) <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    <input type="text" name="new_{{ $name }}" value="{{ old('new_'.$name) }}"
                                           placeholder="Enter new {{ strtolower($field['label']) }}"
                                           class="form-control mt-2 new-value-input @error('new_'.$name) is-invalid @enderror"
                                           maxlength="100" style="{{ $selected === '__new__' ? '' : 'display:none' }}">
                                    @error('new_'.$name) <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            @endforeach
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Save changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('.smart-select').forEach(function (wrap) {
        const select = wrap.querySelector('select');
        const input = wrap.querySelector('.new-value-input');
        select.addEventListener('change', function () {
            const isNew = select.value === '__new__';
            input.style.display = isNew ? '' : 'none';
            if (!isNew) input.value = '';
        });
    });

    document.getElementById('image').addEventListener('change', function (event) {
        const file = event.target.files[0];
        if (file) document.getElementById('imagePreview').src = URL.createObjectURL(file);
    });
</script>
</body>
</html>
