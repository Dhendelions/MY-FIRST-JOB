<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SpreadsheetColumn extends Model
{
    use HasFactory;

    protected $fillable = [
        'sheet_id',
        'letter',
        'title',
        'width',
        'sort_order',
    ];

    /**
     * Parent sheet.
     */
    public function sheet(): BelongsTo
    {
        return $this->belongsTo(SpreadsheetSheet::class, 'sheet_id');
    }

    /**
     * Cells belonging to this column.
     */
    public function cells(): HasMany
    {
        return $this->hasMany(SpreadsheetCell::class, 'column_id');
    }

    /**
     * Check how many cells have non-empty values.
     */
    public function populatedCellsCount(): int
    {
        return $this->cells()
            ->whereNotNull('value')
            ->where('value', '!=', '')
            ->count();
    }
}
