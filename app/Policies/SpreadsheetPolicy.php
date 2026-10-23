<?php

namespace App\Policies;

use App\Models\User;
use App\Models\SpreadsheetSheet;

class SpreadsheetPolicy
{
    /**
     * Determine whether the user can view the spreadsheet sheet.
     */
    public function view(User $user, SpreadsheetSheet $sheet): bool
    {
        $group = $sheet->group;
        return $group && $group->isMember($user->id);
    }

    /**
     * Determine whether the user can edit cell values or insert rows/columns.
     */
    public function edit(User $user, SpreadsheetSheet $sheet): bool
    {
        $group = $sheet->group;
        return $group && $group->isMember($user->id);
    }

    /**
     * Determine whether the user can rename or delete the sheet (Leader or Admin).
     */
    public function manage(User $user, SpreadsheetSheet $sheet): bool
    {
        $group = $sheet->group;
        return $group && $group->isAdmin($user->id);
    }

    /**
     * Determine whether the user can export CSV from sheet.
     */
    public function export(User $user, SpreadsheetSheet $sheet): bool
    {
        return $this->view($user, $sheet);
    }
}
