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

    public static function identityColorPalette(): array
    {
        return config('spreadsheet.sheet_identity_palette', []);
    }

    public static function identityColorKeyForName(string $name): ?string
    {
        $name = mb_strtolower($name);
        foreach (array_keys(self::identityColorPalette()) as $identity) {
            if (str_contains($name, $identity)
                || ($identity === 'analisa' && str_contains($name, 'analysis'))
                || ($identity === 'pendukung' && str_contains($name, 'supporting'))) {
                return $identity;
            }
        }

        return null;
    }

    public static function cyclicIdentityColorKey(int $sheetCount): string
    {
        $keys = array_keys(self::identityColorPalette());
        if ($keys === []) {
            throw new \LogicException('Sheet identity color palette must not be empty.');
        }

        return $keys[$sheetCount % count($keys)];
    }

    public function identityColorKey(): string
    {
        $palette = self::identityColorPalette();
        $stored = $this->metadata['identity_color'] ?? null;
        if (is_string($stored) && array_key_exists($stored, $palette)) {
            return $stored;
        }

        return self::identityColorKeyForName($this->name)
            ?? self::cyclicIdentityColorKey(max(0, (int) $this->getKey() - 1));
    }

    public function identityColor(): array
    {
        return self::identityColorPalette()[$this->identityColorKey()];
    }

    public function isCostWorksheet(): bool
    {
        return ($this->metadata['template_identity'] ?? null) === 'cost'
            || (($this->category ?? null) === 'okt-analisa' && $this->identityColorKey() === 'cost')
            || str_contains(mb_strtolower($this->name), 'cost');
    }

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

    /**
     * Check if this sheet is restricted based on user role.
     * Margin, Pendukung, and Rank are restricted to Leader only.
     */
    public function isRestrictedForRole(?string $role): bool
    {
        if ($role === 'leader') {
            return false;
        }

        $haystack = strtolower($this->name . ' ' . ($this->category ?? ''));
        return str_contains($haystack, 'margin')
            || str_contains($haystack, 'pendukung')
            || str_contains($haystack, 'supporting')
            || str_contains($haystack, 'rank');
    }
}
