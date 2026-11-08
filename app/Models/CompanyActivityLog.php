<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyActivityLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'company_activity_logs';

    protected $fillable = [
        'user_id',
        'user_name',
        'action',
        'resource_type',
        'resource_id',
        'description',
        'properties',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * The user who performed the activity.
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

