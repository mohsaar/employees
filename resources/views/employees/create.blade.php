<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Add New Employee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .avatar-preview { width: 120px; height: 120px; object-fit: cover; border-radius: 50%;
                          background: #e9ecef; border: 2px dashed #adb5bd; }
    </style>
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-9 col-xl-8">

            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center gap-2">
                        <h1 class="h4 mb-0">Add New Employee</h1>
                        <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary btn-sm">Dashboard</a>
                    </div>
                </div>

                <div class="card-body p-4">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-danger">Please fix the highlighted fields below.</div>
                    @endif

                    <form action="{{ route('employees.store') }}" method="POST"
                          enctype="multipart/form-data" novalidate>
                        @csrf

                        {{-- Image --}}
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <img id="imagePreview" class="avatar-preview" alt="Preview"
                                 src="data:image/svg+xml;charset=UTF-8,%3Csvg xmlns='http://www.w3.org/2000/svg'/%3E">
                            <div class="flex-grow-1">
                                <label for="image" class="form-label">Profile image</label>
                                <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.webp"
                                       class="form-control @error('image') is-invalid @enderror">
                                <div class="form-text">JPG, PNG or WEBP, max 2 MB.</div>
                                @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="full_name" class="form-label">Full name <span class="text-danger">*</span></label>
                                <input type="text" id="full_name" name="full_name" value="{{ old('full_name') }}"
                                       class="form-control @error('full_name') is-invalid @enderror" required>
                                @error('full_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" id="email" name="email" value="{{ old('email') }}"
                                       class="form-control @error('email') is-invalid @enderror" required>
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="phone" class="form-label">Phone <span class="text-danger">*</span></label>
                                <input type="tel" id="phone" name="phone" value="{{ old('phone') }}"
                                       class="form-control @error('phone') is-invalid @enderror" required>
                                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="salary_month" class="form-label">Monthly salary</label>
                                <input type="number" step="0.01" min="0" id="salary_month" name="salary_month"
                                       value="{{ old('salary_month') }}"
                                       class="form-control @error('salary_month') is-invalid @enderror">
                                @error('salary_month') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="salary_period" class="form-label">Salary month</label>
                                <input type="month" id="salary_period" name="salary_period"
                                       value="{{ old('salary_period') }}"
                                       class="form-control @error('salary_period') is-invalid @enderror">
                                <div class="form-text">Leave salary and month blank if no salary is assigned yet.</div>
                                @error('salary_period') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Smart selects --}}
                            @php
                                $smartFields = [
                                    ['name' => 'branch',     'label' => 'Branch',     'items' => $branches],
                                    ['name' => 'department', 'label' => 'Department', 'items' => $departments],
                                    ['name' => 'job_title',  'label' => 'Job title',  'items' => $jobTitles],
                                ];
                            @endphp

                            @foreach ($smartFields as $f)
                                @php $n = $f['name']; @endphp
                                <div class="col-12 col-md-4 smart-select">
                                    <label for="{{ $n }}" class="form-label">{{ $f['label'] }} <span class="text-danger">*</span></label>
                                    <select id="{{ $n }}" name="{{ $n }}"
                                            class="form-select @error($n) is-invalid @enderror" required>
                                        <option value="" disabled {{ old($n) ? '' : 'selected' }}>Select…</option>
                                        @foreach ($f['items'] as $item)
                                            <option value="{{ $item->id }}" @selected(old($n) == $item->id)>{{ $item->name }}</option>
                                        @endforeach
                                        <option value="__new__" @selected(old($n) === '__new__')>+ Add new…</option>
                                    </select>
                                    @error($n) <div class="invalid-feedback">{{ $message }}</div> @enderror

                                    <input type="text" name="new_{{ $n }}" value="{{ old('new_'.$n) }}"
                                           placeholder="Enter new {{ strtolower($f['label']) }}"
                                           class="form-control mt-2 new-value-input @error('new_'.$n) is-invalid @enderror"
                                           maxlength="100"
                                           style="{{ old($n) === '__new__' ? '' : 'display:none' }}">
                                    @error('new_'.$n) <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            @endforeach
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="reset" class="btn btn-outline-secondary">Reset</button>
                            <button type="submit" class="btn btn-primary px-4">Save employee</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    // Show the text box only when "+ Add new…" is chosen
    document.querySelectorAll('.smart-select').forEach(function (wrap) {
        const select = wrap.querySelector('select');
        const input  = wrap.querySelector('.new-value-input');

        function toggle() {
            const isNew = select.value === '__new__';
            input.style.display = isNew ? '' : 'none';
            if (isNew) { input.focus(); } else { input.value = ''; }
        }
        select.addEventListener('change', toggle);
    });

    // Live image preview
    document.getElementById('image').addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (file) {
            document.getElementById('imagePreview').src = URL.createObjectURL(file);
        }
    });
</script>
</body>
</html>
