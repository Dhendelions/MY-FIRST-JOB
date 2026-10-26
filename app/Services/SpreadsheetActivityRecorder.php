<?php

namespace App\Services;

use App\Models\SpreadsheetActivity;
use App\Models\SpreadsheetSheet;
use App\Models\FisheriesGroup;
use App\Models\User;
use Illuminate\Support\Str;

class SpreadsheetActivityRecorder
{
    /** Call inside the same transaction as the committed spreadsheet mutation. */
    public function record(User $user, SpreadsheetSheet $sheet, string $action, ?string $range = null, mixed $old = null, mixed $new = null, array $details = []): SpreadsheetActivity
    {
        $role = $sheet->group ? $sheet->group->getMemberRole($user->id) : 'anggota';
        $details['user_role'] = $details['user_role'] ?? $role;
        $details['user_name'] = $details['user_name'] ?? $user->name;
        $details['sheet_name'] = $details['sheet_name'] ?? $sheet->name;

        return SpreadsheetActivity::create([
            'event_uuid' => (string) Str::uuid(),
            'group_id' => $sheet->group_id,
            'sheet_id' => $sheet->id,
            'user_id' => $user->id,
            'action' => $action,
            'cell_range' => $range,
            'old_value' => is_scalar($old) || $old === null ? $old : json_encode($old),
            'new_value' => is_scalar($new) || $new === null ? $new : json_encode($new),
            'details' => $details ?: null,
            'committed_at' => now(),
        ]);
    }

    /** Record workspace-level activities (membership, settings, sheets, etc.) */
    public function recordWorkspace(User $user, FisheriesGroup $group, string $action, mixed $old = null, mixed $new = null, array $details = []): SpreadsheetActivity
    {
        $role = $group->getMemberRole($user->id) ?? 'anggota';
        $details['user_role'] = $details['user_role'] ?? $role;
        $details['user_name'] = $details['user_name'] ?? $user->name;

        return SpreadsheetActivity::create([
            'event_uuid' => (string) Str::uuid(),
            'group_id' => $group->id,
            'sheet_id' => null,
            'user_id' => $user->id,
            'action' => $action,
            'cell_range' => null,
            'old_value' => is_scalar($old) || $old === null ? $old : json_encode($old),
            'new_value' => is_scalar($new) || $new === null ? $new : json_encode($new),
            'details' => $details ?: null,
            'committed_at' => now(),
        ]);
    }
}
