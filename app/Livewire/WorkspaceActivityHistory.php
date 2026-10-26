<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\FisheriesGroup;
use App\Models\SpreadsheetActivity;
use Illuminate\Support\Facades\Auth;

class WorkspaceActivityHistory extends Component
{
    public int $groupId;
    public string $filterAction = 'all';
    public string $search = '';
    /** IDs retained in the current timeline; polling only asks the DB for newer IDs. */
    public array $activityIds = [];
    public int $lastActivityId = 0;

    protected $listeners = [
        'refreshActivityHistory' => 'refreshActivities',
        'cellUpdated' => 'refreshActivities',
    ];

    public function mount(int $groupId): void
    {
        $this->groupId = $groupId;
    }

    public function updatedFilterAction(): void
    {
        $this->resetActivityCursor();
    }

    public function updatedSearch(): void
    {
        $this->resetActivityCursor();
    }

    /** Triggered by polling and by a successful worksheet save. */
    public function refreshActivities(): void
    {
        // render() reads only IDs newer than this cursor and retains the current
        // list, so the normal refresh path does not reload the full history.
    }

    private function resetActivityCursor(): void
    {
        $this->activityIds = [];
        $this->lastActivityId = 0;
    }

    public function render()
    {
        $user = Auth::user();
        if (!$user) {
            abort(401);
        }

        $group = FisheriesGroup::with('sheets')->findOrFail($this->groupId);
        if (!$group->isMember($user->id)) {
            abort(403, 'Akses ditolak.');
        }

        $userRole = $group->getMemberRole($user->id);

        $query = SpreadsheetActivity::with(['user', 'sheet'])
            ->where('group_id', $group->id);
        $latestActivityId = (int) SpreadsheetActivity::where('group_id', $group->id)->max('id');

        // Security: Non-leaders cannot view activity logs for restricted sheets
        if ($userRole !== 'leader') {
            $restrictedSheetIds = $group->sheets
                ->filter(fn ($s) => $s->isRestrictedForRole($userRole))
                ->pluck('id')
                ->all();

            if (!empty($restrictedSheetIds)) {
                $query->where(function ($q) use ($restrictedSheetIds) {
                    $q->whereNull('sheet_id')
                      ->orWhereNotIn('sheet_id', $restrictedSheetIds);
                });
            }
        }

        if ($this->filterAction !== 'all') {
            if ($this->filterAction === 'cells') {
                $query->whereIn('action', ['cell_changed', 'formula_changed', 'cells_pasted', 'format_changed']);
            } elseif ($this->filterAction === 'structure') {
                $query->whereIn('action', ['column_added', 'column_deleted', 'row_added', 'row_deleted', 'csv_imported']);
            } elseif ($this->filterAction === 'members') {
                $query->whereIn('action', ['member_added', 'member_removed', 'role_changed']);
            } elseif ($this->filterAction === 'workspace') {
                $query->whereIn('action', ['workspace_updated', 'sheet_added', 'sheet_renamed', 'sheet_duplicated', 'sheet_deleted']);
            }
        }

        if (trim($this->search) !== '') {
            $term = trim($this->search);
            $query->where(function ($q) use ($term) {
                $q->where('cell_range', 'like', "%{$term}%")
                  ->orWhere('action', 'like', "%{$term}%")
                  ->orWhere('old_value', 'like', "%{$term}%")
                  ->orWhere('new_value', 'like', "%{$term}%")
                  ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$term}%"))
                  ->orWhereHas('sheet', fn ($s) => $s->where('name', 'like', "%{$term}%"));
            });
        }

        if ($this->activityIds === []) {
            $this->activityIds = (clone $query)->orderByDesc('id')->take(50)->pluck('id')->all();
            $this->lastActivityId = $latestActivityId;
        } else {
            // IDs are monotonic, so this is an efficient "since last update"
            // query even when many older activity records exist.
            $newIds = (clone $query)->where('id', '>', $this->lastActivityId)
                ->where('id', '<=', $latestActivityId)
                ->orderByDesc('id')->take(50)->pluck('id')->all();
            if ($newIds !== []) {
                $this->activityIds = array_slice(
                    array_values(array_unique(array_merge($newIds, $this->activityIds))),
                    0,
                    50
                );
            }
            $this->lastActivityId = max($this->lastActivityId, $latestActivityId);
        }

        $activities = $this->activityIds === []
            ? collect()
            : (clone $query)->whereIn('id', $this->activityIds)
                ->orderByDesc('committed_at')->orderByDesc('id')->get();

        return view('livewire.workspace-activity-history', [
            'group' => $group,
            'userRole' => $userRole,
            'activities' => $activities,
        ]);
    }
}
