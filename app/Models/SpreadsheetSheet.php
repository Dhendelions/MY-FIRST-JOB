<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class SpreadsheetSheet extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'name',
        'category',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Group relationship.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(FisheriesGroup::class, 'group_id');
    }

    /**
     * Columns of this spreadsheet sheet.
     */
    public function columns(): HasMany
    {
        return $this->hasMany(SpreadsheetColumn::class, 'sheet_id')->orderBy('sort_order');
    }

    /**
     * Rows of this spreadsheet sheet.
     */
    public function rows(): HasMany
    {
        return $this->hasMany(SpreadsheetRow::class, 'sheet_id')->orderBy('sort_order');
    }

    /**
     * Initialize initial blank grid with A-F columns and 12 empty rows (No default names, purely Excel-style blank).
     */
    public function initializeBlankGrid(int $rowCount = 12, int $colCount = 6): void
    {
        for ($i = 0; $i < $colCount; $i++) {
            $letter = self::columnLetterForIndex($i + 1);
            $this->columns()->create([
                'letter' => $letter,
                'title' => '', // Pure blank column title as required
                'width' => 160,
                'sort_order' => $i + 1,
            ]);
        }

        for ($r = 1; $r <= $rowCount; $r++) {
            $this->rows()->create([
                'sort_order' => $r,
            ]);
        }
    }

    /**
     * Helper to compute next column letter.
     */
    public function getNextColumnLetter(): string
    {
        $lastCol = $this->columns()->orderByDesc('sort_order')->first();
        if (!$lastCol) {
            return 'A';
        }

        $letter = $lastCol->letter;
        return ++$letter;
    }

    private static function columnLetterForIndex(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $remainder = ($index - 1) % 26;
            $letter = chr(65 + $remainder) . $letter;
            $index = intdiv($index - 1, 26);
        }

        return $letter;
    }
}
