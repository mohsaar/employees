<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Employee extends Model
{
    protected $fillable = [
        'full_name', 'email', 'phone',
        'branch_id', 'department_id', 'job_title_id',
        'salary_month', 'salary_period', 'image_path',
    ];

    protected $casts = ['salary_month' => 'decimal:2'];

    public function branch(): BelongsTo     { return $this->belongsTo(Branch::class); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function jobTitle(): BelongsTo   { return $this->belongsTo(JobTitle::class); }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? asset('storage/' . $this->image_path) : null;
    }
}
