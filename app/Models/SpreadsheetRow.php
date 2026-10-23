<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SpreadsheetRow extends Model
{
    use HasFactory;

    protected $fillable = [
        'sheet_id',
        'sort_order',
        'height',
    ];

    /**
     * Parent sheet.
     */
    public function sheet(): BelongsTo
    {
        return $this->belongsTo(SpreadsheetSheet::class, 'sheet_id');
    }

    /**
     * Cells belonging to this row.
     */
    public function cells(): HasMany
    {
        return $this->hasMany(SpreadsheetCell::class, 'row_id');
    }
}
