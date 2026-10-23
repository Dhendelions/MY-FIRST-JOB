<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpreadsheetActivity extends Model
{
    protected $fillable = ['event_uuid', 'group_id', 'sheet_id', 'user_id', 'action', 'cell_range', 'old_value', 'new_value', 'details', 'committed_at'];
    protected $casts = ['details' => 'array', 'committed_at' => 'datetime'];
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function sheet(): BelongsTo { return $this->belongsTo(SpreadsheetSheet::class); }
}
