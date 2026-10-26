<?php

namespace Tests\Feature;

use App\Models\FisheriesGroup;
use App\Models\GroupMember;
use App\Models\SpreadsheetCell;
use App\Models\SpreadsheetActivity;
use App\Models\User;
use App\Services\SpreadsheetWorkbookCalculator;
use App\Services\OktAnalisaTemplateImporter;
use App\Services\SpreadsheetFormulaEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Livewire\SpreadsheetWorksheet;
use Tests\TestCase;

class SpreadsheetFormulaAndInteractionTest extends TestCase
{
    use RefreshDatabase;

    public function test_okt_analisa_formula_displayed_in_formula_bar_on_select(): void
    {
        $user = User::create(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => 'password']);
        $group = FisheriesGroup::create([
            'name' => 'Okt Analisa Group',
            'invite_code' => 'WS-OKT-01',
            'location' => 'Utama',
            'created_by' => $user->id,
        ]);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $user->id, 'role' => 'leader', 'status' => 'Aktif']);

        app(OktAnalisaTemplateImporter::class)->import($group);

        $analisaSheet = $group->sheets()->where('name', 'Analisa')->firstOrFail();
        $colM = $analisaSheet->columns()->where('letter', 'M')->firstOrFail();
        $row5 = $analisaSheet->rows()->where('sort_order', 5)->firstOrFail();
        $m5Cell = SpreadsheetCell::where('row_id', $row5->id)->where('column_id', $colM->id)->firstOrFail();

        $this->assertSame('=C5+E5+G5+I5+K5', $m5Cell->formula);

        // Test Livewire component selection
        $comp = Livewire::actingAs($user)
            ->test(SpreadsheetWorksheet::class, ['sheet' => $analisaSheet])
            ->call('selectCell', $row5->id, $colM->id, 'M5')
            ->assertSet('activeCellCoord', 'M5')
            ->assertSet('formulaValue', '=C5+E5+G5+I5+K5')
            ->assertSet('isEditingCell', false);
    }

    public function test_okt_analisa_source_input_change_recalculates_dependent_formulas_automatically(): void
    {
        $user = User::create(['name' => 'Owner', 'email' => 'owner2@example.test', 'password' => 'password']);
        $group = FisheriesGroup::create([
            'name' => 'Okt Recalc Group',
            'invite_code' => 'WS-OKT-02',
            'location' => 'Utama',
            'created_by' => $user->id,
        ]);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $user->id, 'role' => 'leader', 'status' => 'Aktif']);

        app(OktAnalisaTemplateImporter::class)->import($group);

        $analisaSheet = $group->sheets()->where('name', 'Analisa')->firstOrFail();
        $costSheet = $group->sheets()->where('name', 'Cost')->firstOrFail();

        $colC = $analisaSheet->columns()->where('letter', 'C')->firstOrFail();
        $row5 = $analisaSheet->rows()->where('sort_order', 5)->firstOrFail();
        $c5Cell = SpreadsheetCell::where('row_id', $row5->id)->where('column_id', $colC->id)->firstOrFail();

        $colM = $analisaSheet->columns()->where('letter', 'M')->firstOrFail();
        $m5Cell = SpreadsheetCell::where('row_id', $row5->id)->where('column_id', $colM->id)->firstOrFail();

        $costColC = $costSheet->columns()->where('letter', 'C')->firstOrFail();
        $costRow3 = $costSheet->rows()->where('sort_order', 3)->firstOrFail();
        $costC3 = SpreadsheetCell::where('row_id', $costRow3->id)->where('column_id', $costColC->id)->firstOrFail();

        $costColW = $costSheet->columns()->where('letter', 'W')->firstOrFail();
        $costW3 = SpreadsheetCell::where('row_id', $costRow3->id)->where('column_id', $costColW->id)->firstOrFail();

        // Template imports formula and styling, but never the XLSX's sample
        // transaction figures.
        $this->assertSame('', $c5Cell->value);
        $this->assertSame('0', $m5Cell->value);
        $this->assertSame('0', $costC3->value);
        $this->assertSame('0', $costW3->value);

        // A new input must still propagate through same-sheet and cross-sheet formulas.
        Livewire::actingAs($user)
            ->test(SpreadsheetWorksheet::class, ['sheet' => $analisaSheet])
            ->call('updateCell', $row5->id, $colC->id, '2500', $c5Cell->version);

        $m5Cell->refresh();
        $costC3->refresh();
        $costW3->refresh();

        $this->assertSame('2500', $m5Cell->value);
        $this->assertSame('=C5+E5+G5+I5+K5', $m5Cell->formula);

        // Cross-sheet dependencies must also be updated
        $this->assertSame('2500', $costC3->value);
        $this->assertSame('=Analisa!C5', $costC3->formula);
        $this->assertSame('2500', $costW3->value);
    }

    public function test_single_click_selects_without_entering_edit_mode(): void
    {
        $user = User::create(['name' => 'Owner', 'email' => 'owner3@example.test', 'password' => 'password']);
        $group = FisheriesGroup::create([
            'name' => 'Select Group',
            'invite_code' => 'WS-SEL-01',
            'location' => 'Utama',
            'created_by' => $user->id,
        ]);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $user->id, 'role' => 'leader', 'status' => 'Aktif']);
        $group->initializeStandardSheets();
        $sheet = $group->sheets()->firstOrFail();
        $row = $sheet->rows()->firstOrFail();
        $col = $sheet->columns()->firstOrFail();

        Livewire::actingAs($user)
            ->test(SpreadsheetWorksheet::class, ['sheet' => $sheet])
            ->call('selectCell', $row->id, $col->id, 'A1')
            ->assertSet('activeCellCoord', 'A1')
            ->assertSet('isEditingCell', false);
    }

    public function test_cell_editing_state_and_cancel_edit(): void
    {
        $user = User::create(['name' => 'Owner', 'email' => 'owner4@example.test', 'password' => 'password']);
        $group = FisheriesGroup::create([
            'name' => 'Edit Group',
            'invite_code' => 'WS-EDIT-01',
            'location' => 'Utama',
            'created_by' => $user->id,
        ]);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $user->id, 'role' => 'leader', 'status' => 'Aktif']);
        $group->initializeStandardSheets();
        $sheet = $group->sheets()->firstOrFail();
        $row = $sheet->rows()->firstOrFail();
        $col = $sheet->columns()->firstOrFail();

        SpreadsheetCell::create(['row_id' => $row->id, 'column_id' => $col->id, 'value' => 'Nilai Awal']);

        $comp = Livewire::actingAs($user)
            ->test(SpreadsheetWorksheet::class, ['sheet' => $sheet]);

        // Start editing
        $comp->call('startCellEdit')
            ->assertSet('isEditingCell', true);

        // Cancel editing without saving
        $comp->call('cancelCellEdit')
            ->assertSet('isEditingCell', false);

        // Assert cell value was not modified and no activity recorded
        $this->assertSame('Nilai Awal', SpreadsheetCell::where('row_id', $row->id)->where('column_id', $col->id)->firstOrFail()->value);
        $this->assertSame(0, SpreadsheetActivity::where('sheet_id', $sheet->id)->count());
    }

    public function test_formula_syntax_validation_rejects_malformed_formula_and_preserves_old_data(): void
    {
        $user = User::create(['name' => 'Owner', 'email' => 'owner5@example.test', 'password' => 'password']);
        $group = FisheriesGroup::create([
            'name' => 'Syntax Group',
            'invite_code' => 'WS-SYN-01',
            'location' => 'Utama',
            'created_by' => $user->id,
        ]);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $user->id, 'role' => 'leader', 'status' => 'Aktif']);
        $group->initializeStandardSheets();
        $sheet = $group->sheets()->firstOrFail();
        $rows = $sheet->rows()->orderBy('sort_order')->take(3)->get();
        $col = $sheet->columns()->firstOrFail();

        SpreadsheetCell::create(['row_id' => $rows[1]->id, 'column_id' => $col->id, 'value' => '40']);
        SpreadsheetCell::create(['row_id' => $rows[2]->id, 'column_id' => $col->id, 'value' => '60']);

        $original = SpreadsheetCell::create([
            'row_id' => $rows[0]->id,
            'column_id' => $col->id,
            'value' => '100',
            'formula' => '=SUM(A2:A3)',
        ]);

        // Attempt to update with invalid syntax (unbalanced parens)
        Livewire::actingAs($user)
            ->test(SpreadsheetWorksheet::class, ['sheet' => $sheet])
            ->call('updateCell', $rows[0]->id, $col->id, '=SUM(A2:A5')
            ->assertDispatched('toast');

        // Original formula and value must be strictly preserved
        $original->refresh();
        $this->assertSame('=SUM(A2:A3)', $original->formula);
        $this->assertSame('100', $original->value);

        // Attempt to update with trailing operator
        Livewire::actingAs($user)
            ->test(SpreadsheetWorksheet::class, ['sheet' => $sheet])
            ->call('updateCell', $rows[0]->id, $col->id, '=A2+')
            ->assertDispatched('toast');

        $original->refresh();
        $this->assertSame('=SUM(A2:A3)', $original->formula);
    }

    public function test_valid_formula_update_persists_and_evaluates(): void
    {
        $user = User::create(['name' => 'Owner', 'email' => 'owner6@example.test', 'password' => 'password']);
        $group = FisheriesGroup::create([
            'name' => 'Valid Formula Group',
            'invite_code' => 'WS-VAL-01',
            'location' => 'Utama',
            'created_by' => $user->id,
        ]);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $user->id, 'role' => 'leader', 'status' => 'Aktif']);
        $group->initializeStandardSheets();
        $sheet = $group->sheets()->firstOrFail();
        $rows = $sheet->rows()->orderBy('sort_order')->take(3)->get();
        $col = $sheet->columns()->firstOrFail();

        SpreadsheetCell::create(['row_id' => $rows[0]->id, 'column_id' => $col->id, 'value' => '20']);
        SpreadsheetCell::create(['row_id' => $rows[1]->id, 'column_id' => $col->id, 'value' => '30']);
        $formulaCell = SpreadsheetCell::create(['row_id' => $rows[2]->id, 'column_id' => $col->id, 'value' => '']);

        Livewire::actingAs($user)
            ->test(SpreadsheetWorksheet::class, ['sheet' => $sheet])
            ->call('updateCell', $rows[2]->id, $col->id, '=A1+A2', 0);

        $formulaCell->refresh();
        $this->assertSame('=A1+A2', $formulaCell->formula);
        $this->assertSame('50', $formulaCell->value);
    }

    public function test_activity_history_records_full_audit_details_in_asia_jakarta(): void
    {
        $user = User::create(['name' => 'Audit Tester', 'email' => 'audit@example.test', 'password' => 'password']);
        $group = FisheriesGroup::create([
            'name' => 'Audit Group',
            'invite_code' => 'WS-AUD-01',
            'location' => 'Utama',
            'created_by' => $user->id,
        ]);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $user->id, 'role' => 'leader', 'status' => 'Aktif']);
        $group->initializeStandardSheets();
        $sheet = $group->sheets()->firstOrFail();
        $row = $sheet->rows()->firstOrFail();
        $col = $sheet->columns()->firstOrFail();

        SpreadsheetCell::create(['row_id' => $row->id, 'column_id' => $col->id, 'value' => '10']);

        Livewire::actingAs($user)
            ->test(SpreadsheetWorksheet::class, ['sheet' => $sheet])
            ->call('updateCell', $row->id, $col->id, '=SUM(A2:A3)', 0);

        $activity = SpreadsheetActivity::where('sheet_id', $sheet->id)->latest('committed_at')->firstOrFail();
        $this->assertSame('formula_changed', $activity->action);
        $this->assertSame('A1', $activity->cell_range);
        $this->assertSame('=SUM(A2:A3)', $activity->new_value);
        $this->assertSame('10', $activity->old_value);
        $this->assertSame($user->id, $activity->user_id);
        $this->assertSame('Audit Tester', $activity->details['user_name']);
        $this->assertSame('=SUM(A2:A3)', $activity->details['new_formula']);
        $this->assertNotNull($activity->committed_at);
    }
}
