<?php

namespace App\Services;

use App\Models\SpreadsheetActivity;
use App\Models\SpreadsheetSheet;
use App\Models\User;
use Illuminate\Support\Str;

class SpreadsheetActivityRecorder
{
    /** Call inside the same transaction as the committed spreadsheet mutation. */
    public function record(User $user, SpreadsheetSheet $sheet, string $action, ?string $range = null, mixed $old = null, mixed $new = null, array $details = []): SpreadsheetActivity
    {
        return SpreadsheetActivity::create([
            'event_uuid' => (string) Str::uuid(), 'group_id' => $sheet->group_id, 'sheet_id' => $sheet->id,
            'user_id' => $user->id, 'action' => $action, 'cell_range' => $range,
            'old_value' => is_scalar($old) || $old === null ? $old : json_encode($old),
            'new_value' => is_scalar($new) || $new === null ? $new : json_encode($new),
            'details' => $details ?: null, 'committed_at' => now(),
        ]);
    }
}
