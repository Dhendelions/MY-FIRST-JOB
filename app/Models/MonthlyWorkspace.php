<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonthlyWorkspace extends Model
{
    use HasFactory;

    public const INDONESIAN_MONTHS = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    protected $fillable = [
        'year_id',
        'month',
        'name',
        'group_id',
        'status',
    ];

    protected $casts = [
        'month' => 'integer',
        'year_id' => 'integer',
        'group_id' => 'integer',
    ];

    /**
     * Relationship: The operational year this monthly workspace belongs to.
     */
    public function year(): BelongsTo
    {
        return $this->belongsTo(OperationalYear::class, 'year_id');
    }

    /**
     * Relationship: The underlying workspace group.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(FisheriesGroup::class, 'group_id');
    }

    /**
     * Relationship: Spreadsheet sheets belonging to this monthly workspace.
     */
    public function sheets(): HasMany
    {
        return $this->hasMany(SpreadsheetSheet::class, 'monthly_workspace_id');
    }

    /**
     * Helper: Month name in Indonesian.
     */
    public static function getMonthName(int $month): string
    {
        return self::INDONESIAN_MONTHS[$month] ?? "Bulan {$month}";
    }

    /**
     * Formatted label, e.g. "Oktober 2026".
     */
    public function getFormattedPeriodAttribute(): string
    {
        $yearVal = $this->year?->year ?? '';
        return trim("{$this->name} {$yearVal}");
    }
}

