<?php

namespace Tests\Feature;

use App\Livewire\SpreadsheetWorksheet;
use App\Models\FisheriesGroup;
use App\Models\GroupMember;
use App\Models\SpreadsheetCell;
use App\Models\SpreadsheetSheet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SpreadsheetModuleConsistencyTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected FisheriesGroup $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Module Performance Owner',
            'email' => 'module-performance@example.test',
            'password' => 'password',
        ]);
        $this->group = FisheriesGroup::create([
            'name' => 'Module Performance Group',
            'invite_code' => 'MODULE-PERF-01',
            'location' => 'Utama',
            'created_by' => $this->user->id,
        ]);
        GroupMember::create([
            'group_id' => $this->group->id,
            'user_id' => $this->user->id,
            'role' => 'leader',
            'status' => 'Aktif',
        ]);
        foreach (array_keys(SpreadsheetSheet::identityColorPalette()) as $index => $name) {
            $sheet = $this->group->sheets()->create([
                'name' => ucfirst($name),
                'category' => $name,
                'metadata' => ['identity_color' => $name],
            ]);
            $sheet->initializeBlankGrid();
        }
    }

    public static function standardModules(): array
    {
        return array_map(fn (string $name) => [$name], ['Analisa', 'Cost', 'Margin', 'Pendukung', 'Rank']);
    }

    #[DataProvider('standardModules')]
    public function test_each_module_starts_unselected_and_uses_the_shared_interaction_surface(string $name): void
    {
        $sheet = $this->group->sheets()->where('name', $name)->firstOrFail();

        $component = Livewire::actingAs($this->user)
            ->test(SpreadsheetWorksheet::class, ['sheet' => $sheet])
            ->assertSet('activeCellCoord', '')
            ->assertSet('activeRowId', null)
            ->assertSet('activeColumnId', null);

        $html = $component->html();
        $this->assertStringContainsString('x-data="spreadsheetWorksheet($wire)"', $html);
        $this->assertStringContainsString('class="flex-1 overflow-auto min-h-0 min-w-0 bg-slate-200 select-text relative spreadsheet-scroll"', $html);
        $this->assertStringContainsString('isolation: isolate', $html);
        $this->assertStringContainsString('z-40', $html);
        $this->assertStringNotContainsString('border-slate-200/60 is-selected', $html);
    }

    #[DataProvider('standardModules')]
    public function test_each_module_persists_edits_and_recalculates_its_own_dependent_formula(string $name): void
    {
        $sheet = $this->group->sheets()->where('name', $name)->firstOrFail();
        $row = $sheet->rows()->orderBy('sort_order')->firstOrFail();
        $columnA = $sheet->columns()->where('sort_order', 1)->firstOrFail();
        $columnB = $sheet->columns()->where('sort_order', 2)->firstOrFail();

        $component = Livewire::actingAs($this->user)
            ->test(SpreadsheetWorksheet::class, ['sheet' => $sheet]);

        $component->call('updateCell', $row->id, $columnA->id, '12', 0);
        $component->call('updateCell', $row->id, $columnB->id, '=A1*2', 0);

        $formulaCell = SpreadsheetCell::query()
            ->where('row_id', $row->id)
            ->where('column_id', $columnB->id)
            ->firstOrFail();
        $this->assertSame('24', $formulaCell->value);
        $this->assertSame('=A1*2', $formulaCell->formula);

        $source = SpreadsheetCell::query()
            ->where('row_id', $row->id)
            ->where('column_id', $columnA->id)
            ->firstOrFail();
        $component->call('updateCell', $row->id, $columnA->id, '21', $source->version);

        $this->assertSame('42', $formulaCell->fresh()->value);
    }

    public function test_cost_selection_stays_local_and_formula_commits_remain_correct(): void
    {
        $sheet = $this->group->sheets()->where('name', 'Cost')->firstOrFail();
        $row = $sheet->rows()->orderBy('sort_order')->firstOrFail();
        $columnA = $sheet->columns()->where('sort_order', 1)->firstOrFail();
        $columnB = $sheet->columns()->where('sort_order', 2)->firstOrFail();

        $component = Livewire::actingAs($this->user)
            ->test(SpreadsheetWorksheet::class, ['sheet' => $sheet])
            ->assertSet('activeCellCoord', '')
            ->assertSee('maybeExtendGridForCell', false);

        $component->call('updateCell', $row->id, $columnA->id, '15', 0);
        $component->call('updateCell', $row->id, $columnB->id, '=A1*3', 0);

        $this->assertSame('45', SpreadsheetCell::query()
            ->where('row_id', $row->id)
            ->where('column_id', $columnB->id)
            ->value('value'));
    }

    public function test_rank_selection_stays_local_and_formula_commits_remain_correct(): void
    {
        $sheet = $this->group->sheets()->where('name', 'Rank')->firstOrFail();
        $row = $sheet->rows()->orderBy('sort_order')->firstOrFail();
        $columnA = $sheet->columns()->where('sort_order', 1)->firstOrFail();
        $columnB = $sheet->columns()->where('sort_order', 2)->firstOrFail();

        $component = Livewire::actingAs($this->user)
            ->test(SpreadsheetWorksheet::class, ['sheet' => $sheet])
            ->assertSet('activeCellCoord', '')
            ->assertSee('maybeExtendGridForCell', false);

        $component->call('updateCell', $row->id, $columnA->id, '18', 0);
        $component->call('updateCell', $row->id, $columnB->id, '=A1*3', 0);

        $this->assertSame('54', SpreadsheetCell::query()
            ->where('row_id', $row->id)
            ->where('column_id', $columnB->id)
            ->value('value'));
    }

}
