<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpreadsheetCell extends Model
{
    use HasFactory;

    protected $fillable = [
        'row_id',
        'column_id',
        'value',
        'formula',
        'format',
        'version',
    ];

    protected $casts = [
        'format' => 'array',
    ];

    /**
     * Parent row.
     */
    public function row(): BelongsTo
    {
        return $this->belongsTo(SpreadsheetRow::class, 'row_id');
    }

    /**
     * Parent column.
     */
    public function column(): BelongsTo
    {
        return $this->belongsTo(SpreadsheetColumn::class, 'column_id');
    }

    /**
     * Check if cell holds a numeric or currency value.
     */
    public function isNumericValue(): bool
    {
        if ($this->value === null || trim($this->value) === '') {
            return false;
        }

        $clean = preg_replace('/[^\d\.\,\-]/', '', $this->value);
        return is_numeric(str_replace(',', '.', $clean));
    }

    /**
     * Extract raw float number.
     */
    public function getNumericValue(): float
    {
        if (!$this->isNumericValue()) {
            return 0.0;
        }

        $clean = preg_replace('/[^\d\.\,\-]/', '', $this->value);
        return (float) str_replace(',', '.', $clean);
    }
}
