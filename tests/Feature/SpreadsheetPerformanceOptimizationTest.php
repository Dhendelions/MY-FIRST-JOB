<?php

namespace Tests\Feature;

use App\Models\FisheriesGroup;
use App\Models\GroupMember;
use App\Models\SpreadsheetCell;
use App\Models\SpreadsheetSheet;
use App\Models\SpreadsheetColumn;
use App\Models\SpreadsheetRow;
use App\Models\User;
use App\Livewire\SpreadsheetWorksheet;
use App\Services\SpreadsheetFormulaEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SpreadsheetPerformanceOptimizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected FisheriesGroup $group;
    protected SpreadsheetSheet $sheet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Tester Performance',
            'email' => 'perf@example.test',
            'password' => 'password',
        ]);

        $this->group = FisheriesGroup::create([
            'name' => 'Performance Testing Group',
            'invite_code' => 'PERF-01',
            'location' => 'Tambak Utama',
            'created_by' => $this->user->id,
        ]);

        GroupMember::create([
            'group_id' => $this->group->id,
            'user_id' => $this->user->id,
            'role' => 'leader',
            'status' => 'Aktif',
        ]);

        $this->group->initializeStandardSheets();
        $this->sheet = $this->group->sheets()->firstOrFail();
    }

    public function test_switching_sheets_reuses_the_worksheet_and_loads_only_the_selected_sheet(): void
    {
        $sheets = $this->group->sheets()->orderBy('id')->get();
        $sheetNames = ['Analisa', 'Cost', 'Margin', 'Pendukung', 'Rank'];

        foreach ($sheets as $index => $sheet) {
            $sheet->update(['name' => $sheetNames[$index]]);
            SpreadsheetCell::create([
                'row_id' => $sheet->rows()->firstOrFail()->id,
                'column_id' => $sheet->columns()->firstOrFail()->id,
                'value' => $sheetNames[$index] . ' data',
            ]);
        }
        $this->sheet = $sheets->first()->fresh();

        $component = Livewire::actingAs($this->user)
            ->test(SpreadsheetWorksheet::class, ['sheet' => $this->sheet]);

        $switchDuration = null;
        foreach ($sheets->skip(1) as $targetSheet) {
            $targetRow = $targetSheet->rows()->firstOrFail();
            $targetColumn = $targetSheet->columns()->firstOrFail();

            $startedAt = microtime(true);
            $component->call('switchSheet', $targetSheet->id)
                ->assertSet('sheetId', $targetSheet->id);
            $duration = microtime(true) - $startedAt;
            $switchDuration ??= $duration;

            $this->assertSame(
                [$targetRow->id . '_' . $targetColumn->id => $targetSheet->name . ' data'],
                $component->get('cellsData')
            );
        }

        $this->assertLessThan(3.0, $switchDuration, 'An in-place sheet switch should complete within 3 seconds.');
    }

    public function test_in_place_sheet_switch_preserves_restricted_sheet_access(): void
    {
        $staff = User::create([
            'name' => 'Restricted Sheet Tester',
            'email' => 'restricted-perf@example.test',
            'password' => 'password',
        ]);
        GroupMember::create([
            'group_id' => $this->group->id,
            'user_id' => $staff->id,
            'role' => 'karyawan',
            'status' => 'Aktif',
        ]);
        $restrictedSheet = SpreadsheetSheet::create([
            'group_id' => $this->group->id,
            'name' => 'Rank',
            'category' => 'rank',
        ]);

        Livewire::actingAs($staff)
            ->test(SpreadsheetWorksheet::class, ['sheet' => $this->sheet])
            ->call('switchSheet', $restrictedSheet->id)
            ->assertForbidden();
    }

    /**
     * Test 1: Repeated rapid typing & cell editing performance.
     * Verifies that consecutive cell edits update smoothly and maintain consistency.
     */
    public function test_repeated_rapid_cell_edits_execute_fast_and_persist_correctly(): void
    {
        $rows = $this->sheet->rows()->orderBy('sort_order')->take(10)->get();
        $colA = $this->sheet->columns()->where('sort_order', 1)->firstOrFail();

        $component = Livewire::actingAs($this->user)
            ->test(SpreadsheetWorksheet::class, ['sheet' => $this->sheet]);

        $startTime = microtime(true);

        // Simulate repeated typing in 10 consecutive cells
        for ($i = 0; $i < 10; $i++) {
            $row = $rows[$i];
            $value = 'Value ' . ($i + 1);
            $component->call('updateCell', $row->id, $colA->id, $value, 0);
        }

        $elapsed = microtime(true) - $startTime;

        // Verify all 10 cells were persisted accurately
        for ($i = 0; $i < 10; $i++) {
            $row = $rows[$i];
            $this->assertDatabaseHas('spreadsheet_cells', [
                'row_id' => $row->id,
                'column_id' => $colA->id,
                'value' => 'Value ' . ($i + 1),
            ]);
        }

        // 10 consecutive updates must complete with good throughput
        $this->assertLessThan(5.0, $elapsed, '10 rapid edits should complete within 5 seconds');
    }

    /**
     * Test 2: Formula calculation & dependent cells recalculation.
     * Setting A1=50 and B1=100 with C1="=A1+B1" should yield 150 immediately.
     */
    public function test_formula_and_dependent_cell_calculation(): void
    {
        $row1 = $this->sheet->rows()->where('sort_order', 1)->firstOrFail();
        $colA = $this->sheet->columns()->where('sort_order', 1)->firstOrFail();
        $colB = $this->sheet->columns()->where('sort_order', 2)->firstOrFail();
        $colC = $this->sheet->columns()->where('sort_order', 3)->firstOrFail();

        $comp = Livewire::actingAs($this->user)
            ->test(SpreadsheetWorksheet::class, ['sheet' => $this->sheet]);

        // Set A1 = 50
        $comp->call('updateCell', $row1->id, $colA->id, '50', 0);
        // Set B1 = 100
        $comp->call('updateCell', $row1->id, $colB->id, '100', 0);
        // Set C1 = =A1+B1
        $comp->call('updateCell', $row1->id, $colC->id, '=A1+B1', 0);

        $cellC1 = SpreadsheetCell::where('row_id', $row1->id)->where('column_id', $colC->id)->firstOrFail();
        $this->assertSame('150', $cellC1->value);
        $this->assertSame('=A1+B1', $cellC1->formula);

        // Now modify A1 to 200, C1 should automatically recalculate to 300
        $cellA1 = SpreadsheetCell::where('row_id', $row1->id)->where('column_id', $colA->id)->firstOrFail();
        $comp->call('updateCell', $row1->id, $colA->id, '200', $cellA1->version);

        $cellC1->refresh();
        $this->assertSame('300', $cellC1->value);
    }

    /**
     * Test 3: SUM range calculation e.g. =SUM(A1:A5).
     */
    public function test_sum_range_formula_calculation(): void
    {
        $rows = $this->sheet->rows()->orderBy('sort_order')->take(5)->get();
        $colA = $this->sheet->columns()->where('sort_order', 1)->firstOrFail();
        $colB = $this->sheet->columns()->where('sort_order', 2)->firstOrFail();

        $comp = Livewire::actingAs($this->user)
            ->test(SpreadsheetWorksheet::class, ['sheet' => $this->sheet]);

        // Fill A1 to A5 with 10, 20, 30, 40, 50 (Sum = 150)
        for ($i = 0; $i < 5; $i++) {
            $comp->call('updateCell', $rows[$i]->id, $colA->id, (string) (($i + 1) * 10), 0);
        }

        // Set B1 = =SUM(A1:A5)
        $comp->call('updateCell', $rows[0]->id, $colB->id, '=SUM(A1:A5)', 0);

        $sumCell = SpreadsheetCell::where('row_id', $rows[0]->id)->where('column_id', $colB->id)->firstOrFail();
        $this->assertSame('150', $sumCell->value);
    }

    /**
     * Test 4: Multi-cell TSV Paste with large dataset.
     */
    public function test_large_tsv_paste_performance(): void
    {
        $row1 = $this->sheet->rows()->where('sort_order', 1)->firstOrFail();
        $colA = $this->sheet->columns()->where('sort_order', 1)->firstOrFail();

        // Build a 15-row x 5-column TSV grid (75 cells total)
        $lines = [];
        for ($r = 1; $r <= 15; $r++) {
            $rowValues = [];
            for ($c = 1; $c <= 5; $c++) {
                $rowValues[] = "Data R{$r}C{$c}";
            }
            $lines[] = implode("\t", $rowValues);
        }
        $tsvContent = implode("\n", $lines);

        $start = microtime(true);

        Livewire::actingAs($this->user)
            ->test(SpreadsheetWorksheet::class, ['sheet' => $this->sheet])
            ->call('pasteTsvData', $row1->id, $colA->id, $tsvContent);

        $duration = microtime(true) - $start;

        // Verify cells exist in DB
        $this->assertDatabaseHas('spreadsheet_cells', [
            'value' => 'Data R15C5',
        ]);

        $this->assertLessThan(3.0, $duration, 'Large TSV paste should execute under 3 seconds');
    }

    /**
     * Test 5: Format cell with explicit rowId and colId.
     */
    public function test_format_cell_with_passed_coordinates(): void
    {
        $row3 = $this->sheet->rows()->where('sort_order', 3)->firstOrFail();
        $colB = $this->sheet->columns()->where('sort_order', 2)->firstOrFail();

        Livewire::actingAs($this->user)
            ->test(SpreadsheetWorksheet::class, ['sheet' => $this->sheet])
            ->call('formatCell', 'bold', null, $row3->id, $colB->id);

        $cell = SpreadsheetCell::where('row_id', $row3->id)->where('column_id', $colB->id)->firstOrFail();
        $this->assertTrue($cell->format['bold']);
    }

    /**
     * Test 6: Cell selection and edit lifecycle state.
     */
    public function test_selection_and_edit_state_lifecycle(): void
    {
        $row = $this->sheet->rows()->firstOrFail();
        $col = $this->sheet->columns()->firstOrFail();

        $comp = Livewire::actingAs($this->user)
            ->test(SpreadsheetWorksheet::class, ['sheet' => $this->sheet]);

        // Select cell
        $comp->call('selectCell', $row->id, $col->id, 'A1')
            ->assertSet('activeCellCoord', 'A1')
            ->assertSet('isEditingCell', false);

        // Start edit
        $comp->call('startCellEdit')
            ->assertSet('isEditingCell', true);

        // Cancel edit
        $comp->call('cancelCellEdit')
            ->assertSet('isEditingCell', false);
    }

    public function test_stale_cell_version_cannot_overwrite_a_newer_save(): void
    {
        $row = $this->sheet->rows()->where('sort_order', 1)->firstOrFail();
        $column = $this->sheet->columns()->where('sort_order', 1)->firstOrFail();
        SpreadsheetCell::create([
            'row_id' => $row->id,
            'column_id' => $column->id,
            'value' => 'initial',
            'version' => 1,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(SpreadsheetWorksheet::class, ['sheet' => $this->sheet]);

        $component->call('updateCell', $row->id, $column->id, 'newer', 1);
        $saved = SpreadsheetCell::where('row_id', $row->id)->where('column_id', $column->id)->firstOrFail();

        // A delayed request that was composed from the old version must be
        // rejected instead of replacing the value already saved above.
        $component->call('updateCell', $row->id, $column->id, 'older', 1)
            ->assertDispatched('toast');

        $this->assertSame('newer', $saved->fresh()->value);
    }

    public function test_invalid_formula_in_paste_keeps_the_range_unchanged(): void
    {
        $row = $this->sheet->rows()->where('sort_order', 1)->firstOrFail();
        $column = $this->sheet->columns()->where('sort_order', 1)->firstOrFail();
        SpreadsheetCell::create(['row_id' => $row->id, 'column_id' => $column->id, 'value' => 'tetap']);

        Livewire::actingAs($this->user)
            ->test(SpreadsheetWorksheet::class, ['sheet' => $this->sheet])
            ->call('pasteTsvData', $row->id, $column->id, "baru\t=SUM(A1:A2")
            ->assertDispatched('toast');

        $this->assertSame(
            'tetap',
            SpreadsheetCell::where('row_id', $row->id)->where('column_id', $column->id)->value('value')
        );
    }
}
