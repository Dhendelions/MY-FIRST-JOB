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
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use App\Livewire\SpreadsheetWorksheet;
use Tests\TestCase;

class SpreadsheetWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_workspace_creation_creates_a_persistent_blank_grid(): void
    {
        $user = User::create(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => 'password']);

        $response = $this->actingAs($user)->post(route('groups.store'), [
            'name' => 'Workbook test',
            'password' => 'rahasia',
            'workspace_type' => 'manual',
        ]);

        $response->assertRedirect();
        $group = FisheriesGroup::where('name', 'Workbook test')->firstOrFail();
        $this->assertDatabaseHas('group_members', ['group_id' => $group->id, 'user_id' => $user->id, 'role' => 'leader']);
        $this->assertSame(5, $group->sheets()->count());
        $firstSheet = $group->sheets()->firstOrFail();
        $this->assertSame(6, $firstSheet->columns()->count());
        $this->assertSame(12, $firstSheet->rows()->count());
    }

    public function test_formulas_recalculate_and_keep_their_underlying_formula(): void
    {
        [$group, $sheet] = $this->createSheet();
        $rows = $sheet->rows()->orderBy('sort_order')->take(3)->get();
        $columns = $sheet->columns()->orderBy('sort_order')->take(2)->get();

        SpreadsheetCell::create(['row_id' => $rows[0]->id, 'column_id' => $columns[0]->id, 'value' => '5']);
        SpreadsheetCell::create(['row_id' => $rows[1]->id, 'column_id' => $columns[0]->id, 'value' => '7']);
        $total = SpreadsheetCell::create([
            'row_id' => $rows[2]->id,
            'column_id' => $columns[0]->id,
            'value' => '',
            'formula' => '=SUM(A1:A2)',
        ]);
        $product = SpreadsheetCell::create([
            'row_id' => $rows[2]->id,
            'column_id' => $columns[1]->id,
            'value' => '',
            'formula' => '=A1*A2',
        ]);

        app(SpreadsheetWorkbookCalculator::class)->recalculate($group);

        $this->assertSame('12', $total->fresh()->value);
        $this->assertSame('=SUM(A1:A2)', $total->fresh()->formula);
        $this->assertSame('35', $product->fresh()->value);
    }

    public function test_multi_cell_tsv_paste_persists_at_the_expected_coordinates(): void
    {
        [$group, $sheet] = $this->createSheet();
        $user = $group->creator;
        $rows = $sheet->rows()->orderBy('sort_order')->take(2)->get();
        $columns = $sheet->columns()->orderBy('sort_order')->take(2)->get();

        Livewire::actingAs($user)
            ->test(SpreadsheetWorksheet::class, ['sheet' => $sheet])
            ->call('pasteTsvData', $rows[0]->id, $columns[0]->id, "satu\tdua\ntiga\tempat");

        $this->assertDatabaseHas('spreadsheet_cells', ['row_id' => $rows[0]->id, 'column_id' => $columns[0]->id, 'value' => 'satu']);
        $this->assertDatabaseHas('spreadsheet_cells', ['row_id' => $rows[0]->id, 'column_id' => $columns[1]->id, 'value' => 'dua']);
        $this->assertDatabaseHas('spreadsheet_cells', ['row_id' => $rows[1]->id, 'column_id' => $columns[0]->id, 'value' => 'tiga']);
        $this->assertDatabaseHas('spreadsheet_cells', ['row_id' => $rows[1]->id, 'column_id' => $columns[1]->id, 'value' => 'empat']);
    }

    public function test_existing_xlsx_template_imports_layout_metadata_and_formulas(): void
    {
        $user = User::create(['name' => 'Template owner', 'email' => 'template@example.test', 'password' => 'password']);
        $group = FisheriesGroup::create([
            'name' => 'Template group', 'invite_code' => 'WS-TPL-001', 'location' => 'Utama',
            'created_by' => $user->id,
        ]);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $user->id, 'role' => 'leader', 'status' => 'Aktif']);

        app(OktAnalisaTemplateImporter::class)->import($group);

        $this->assertGreaterThan(0, $group->sheets()->count());
        $this->assertNotNull($group->sheets()->whereNotNull('metadata')->first());
        $this->assertGreaterThan(0, SpreadsheetCell::whereHas('row', fn ($query) => $query->whereIn('sheet_id', $group->sheets()->pluck('id')))->whereNotNull('formula')->count());

        $analisa = $group->sheets()->where('name', 'Analisa')->firstOrFail();
        $row5 = $analisa->rows()->where('sort_order', 5)->firstOrFail();
        $columnC = $analisa->columns()->where('letter', 'C')->firstOrFail();
        $columnM = $analisa->columns()->where('letter', 'M')->firstOrFail();
        $sampleInput = SpreadsheetCell::where('row_id', $row5->id)->where('column_id', $columnC->id)->firstOrFail();
        $formulaCell = SpreadsheetCell::where('row_id', $row5->id)->where('column_id', $columnM->id)->firstOrFail();

        $this->assertSame('', $sampleInput->value);
        $this->assertNotEmpty($sampleInput->format);
        $this->assertSame('=C5+E5+G5+I5+K5', $formulaCell->formula);
        $this->assertSame('0', $formulaCell->value);

        $sampleFormulaColumn = $analisa->columns()->where('letter', 'E')->firstOrFail();
        $sampleFormula = SpreadsheetCell::where('row_id', $analisa->rows()->where('sort_order', 4)->value('id'))
            ->where('column_id', $sampleFormulaColumn->id)->firstOrFail();
        $this->assertSame('=617.7+3317', $sampleFormula->formula);
        $this->assertSame('', $sampleFormula->value);
    }

    public function test_adding_a_row_copies_the_data_row_presentation_and_keeps_formula_footer_last(): void
    {
        [$group, $sheet] = $this->createSheet();
        $rows = $sheet->rows()->orderBy('sort_order')->skip(10)->take(2)->get();
        $column = $sheet->columns()->orderBy('sort_order')->firstOrFail();

        SpreadsheetCell::create([
            'row_id' => $rows[0]->id, 'column_id' => $column->id, 'value' => '12',
            'format' => ['bg' => '#e0f2fe', 'align' => 'right'],
        ]);
        SpreadsheetCell::create([
            'row_id' => $rows[1]->id, 'column_id' => $column->id, 'value' => '', 'formula' => '=SUM(A1:A11)',
        ]);

        Livewire::actingAs($group->creator)
            ->test(SpreadsheetWorksheet::class, ['sheet' => $sheet])
            ->call('addRow');

        $newRow = $sheet->rows()->where('sort_order', 12)->firstOrFail();
        $footer = $sheet->rows()->where('sort_order', 13)->firstOrFail();
        $newCell = SpreadsheetCell::where('row_id', $newRow->id)->where('column_id', $column->id)->firstOrFail();
        $footerCell = SpreadsheetCell::where('row_id', $footer->id)->where('column_id', $column->id)->firstOrFail();

        $this->assertSame('', $newCell->value);
        $this->assertSame(['bg' => '#e0f2fe', 'align' => 'right'], $newCell->format);
        $this->assertSame('=SUM(A1:A12)', $footerCell->formula);
    }

    public function test_grid_dimensions_are_persisted_independently_from_cell_data(): void
    {
        [$group, $sheet] = $this->createSheet();
        $row = $sheet->rows()->firstOrFail();
        $column = $sheet->columns()->firstOrFail();

        Livewire::actingAs($group->creator)
            ->test(SpreadsheetWorksheet::class, ['sheet' => $sheet])
            ->call('setColumnWidth', $column->id, 245)
            ->call('setRowHeight', $row->id, 60);

        $this->assertSame(245, $column->fresh()->width);
        $this->assertEqualsWithDelta(45, (float) $row->fresh()->height, 0.01);
    }

    public function test_csv_export_remains_available_for_the_current_sheet(): void
    {
        [$group, $sheet] = $this->createSheet();

        $response = $this->actingAs($group->creator)->get(route('spreadsheet.export', $sheet));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
        $this->assertStringContainsString('.csv', $response->headers->get('content-disposition'));
    }

    public function test_canvas_projects_a_to_z_and_materializes_only_an_interacted_coordinate(): void
    {
        [$group, $sheet] = $this->createSheet();

        $component = Livewire::actingAs($group->creator)
            ->test(SpreadsheetWorksheet::class, ['sheet' => $sheet])
            ->assertSet('gridColumnLimit', 26)
            ->assertSet('gridRowLimit', 30);

        $this->assertSame(780, substr_count($component->html(), 'class="grid-cell-td'));
        $component->call('extendGrid', 'row')
            ->assertSet('gridRowLimit', 55)
            ->call('ensureGridCell', 100, 26);

        $row = $sheet->rows()->where('sort_order', 100)->firstOrFail();
        $column = $sheet->columns()->where('letter', 'Z')->firstOrFail();
        $this->assertDatabaseMissing('spreadsheet_cells', ['row_id' => $row->id, 'column_id' => $column->id]);

        Livewire::actingAs($group->creator)
            ->test(SpreadsheetWorksheet::class, ['sheet' => $sheet])
            ->call('updateCell', $row->id, $column->id, 'Catatan baru', 0);

        $this->assertDatabaseHas('spreadsheet_cells', [
            'row_id' => $row->id, 'column_id' => $column->id, 'value' => 'Catatan baru',
        ]);
    }

    public function test_committed_cell_change_creates_one_persistent_audit_activity(): void
    {
        [$group, $sheet] = $this->createSheet();
        $row = $sheet->rows()->firstOrFail();
        $column = $sheet->columns()->firstOrFail();

        Livewire::actingAs($group->creator)
            ->test(SpreadsheetWorksheet::class, ['sheet' => $sheet])
            ->call('updateCell', $row->id, $column->id, 'Diaudit', 0)
            ->call('updateCell', $row->id, $column->id, 'Diaudit', 1);

        $this->assertSame(1, SpreadsheetActivity::where('sheet_id', $sheet->id)->count());
        $this->assertDatabaseHas('spreadsheet_activities', [
            'group_id' => $group->id, 'sheet_id' => $sheet->id, 'user_id' => $group->creator->id,
            'action' => 'cell_changed', 'cell_range' => 'A1', 'new_value' => 'Diaudit',
        ]);
    }

    public function test_locked_sheet_does_not_render_grid_or_allow_export_until_unlocked(): void
    {
        [$group, $sheet] = $this->createSheet();
        $group->update(['password' => Hash::make('rahasia')]);

        $this->actingAs($group->creator)
            ->get(route('group.workspace', [$group->id, $sheet->id]))
            ->assertOk()
            ->assertDontSee('Area kerja spreadsheet');
        $this->actingAs($group->creator)
            ->get(route('spreadsheet.export', $sheet))
            ->assertForbidden();
    }

    public function test_supporting_sheet_first_column_has_a_readable_persisted_minimum_width(): void
    {
        [$group] = $this->createSheet();
        $supporting = $group->sheets()->create(['name' => 'Harga Pendukung', 'category' => 'pendukung']);
        $supporting->initializeBlankGrid();
        $firstColumn = $supporting->columns()->orderBy('sort_order')->firstOrFail();
        $firstColumn->update(['width' => 52]);

        Livewire::actingAs($group->creator)
            ->test(SpreadsheetWorksheet::class, ['sheet' => $supporting]);

        $this->assertSame(140, $firstColumn->fresh()->width);
    }

    public function test_supported_formula_functions_and_arithmetic_are_evaluated_without_eval(): void
    {
        $grid = ['A1' => '2', 'A2' => '4', 'A3' => '6', 'B1' => '3'];

        $this->assertSame('12', SpreadsheetFormulaEngine::evaluate('=SUM(A1:A3)', $grid));
        $this->assertSame('4', SpreadsheetFormulaEngine::evaluate('=AVERAGE(A1:A3)', $grid));
        $this->assertSame('2', SpreadsheetFormulaEngine::evaluate('=MIN(A1:A3)', $grid));
        $this->assertSame('6', SpreadsheetFormulaEngine::evaluate('=MAX(A1:A3)', $grid));
        $this->assertSame('3', SpreadsheetFormulaEngine::evaluate('=COUNT(A1:A3)', $grid));
        $this->assertSame('14', SpreadsheetFormulaEngine::evaluate('=A1+B1*A2', $grid));
    }

    public function test_formula_engine_evaluates_dollar_percent_compound_functions_and_dashes(): void
    {
        $grid = [
            'K9' => '200',
            'K4' => '1000',
            'C8' => '10',
            'C9' => '20',
            'C15' => '30',
            'C16' => '5',
            'D1' => '-',
            'D2' => '10',
        ];

        // Absolute reference with percent operator
        $this->assertSame('20', SpreadsheetFormulaEngine::evaluate('=K9/K$4%', $grid));
        // Embedded function in arithmetic expression
        $this->assertSame('55', SpreadsheetFormulaEngine::evaluate('=SUM(C8:C15)-C16', $grid));
        // Dash accounting zero treated as 0 in arithmetic
        $this->assertSame('10', SpreadsheetFormulaEngine::evaluate('=D1+D2', $grid));
    }

    public function test_rendered_spreadsheet_does_not_leak_raw_javascript_code(): void
    {
        [$group, $sheet] = $this->createSheet();

        $component = Livewire::actingAs($group->creator)
            ->test(SpreadsheetWorksheet::class, ['sheet' => $sheet]);

        $html = $component->html();

        // Ensure raw JS source code is NOT leaked as visible text in the rendered HTML
        $this->assertStringNotContainsString("cell.style.width = width + 'px'", $html);
        $this->assertStringNotContainsString('const onUp =', $html);
        $this->assertStringNotContainsString("Math.max(64, Math.min(900", $html);
        $this->assertStringContainsString('x-data="spreadsheetWorksheet($wire)"', $html);
        $this->assertStringContainsString('<colgroup>', $html);
    }

    public function test_recalculation_handles_empty_cells_without_value_errors(): void
    {
        $user = User::create(['name' => 'Template Recalc Owner', 'email' => 'recalc@example.test', 'password' => 'password']);
        $group = FisheriesGroup::create([
            'name' => 'Recalc Group',
            'invite_code' => 'WS-RECALC-01',
            'location' => 'Utama',
            'created_by' => $user->id,
        ]);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $user->id, 'role' => 'leader', 'status' => 'Aktif']);

        app(OktAnalisaTemplateImporter::class)->import($group);

        // Verify there are NO #VALUE! errors in any imported sheet
        $valueErrors = SpreadsheetCell::whereHas('row', fn ($q) => $q->whereIn('sheet_id', $group->sheets()->pluck('id')))
            ->where('value', '#VALUE!')
            ->count();

        $this->assertSame(0, $valueErrors);
    }

    public function test_analisa_cost_margin_sticky_headers_and_template_rows(): void
    {
        $user = User::create(['name' => 'Sticky Test Owner', 'email' => 'sticky@example.test', 'password' => 'password']);
        $group = FisheriesGroup::create([
            'name' => 'Sticky Group',
            'invite_code' => 'WS-STICKY-01',
            'location' => 'Utama',
            'created_by' => $user->id,
        ]);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $user->id, 'role' => 'leader', 'status' => 'Aktif']);

        app(OktAnalisaTemplateImporter::class)->import($group);

        $analisaSheet = $group->sheets()->where('name', 'Analisa')->firstOrFail();
        $costSheet = $group->sheets()->where('name', 'Cost')->firstOrFail();
        $marginSheet = $group->sheets()->where('name', 'Margin')->firstOrFail();
        $this->assertSame('analisa', $analisaSheet->metadata['identity_color']);
        $this->assertSame('cost', $costSheet->metadata['identity_color']);
        $this->assertSame('margin', $marginSheet->metadata['identity_color']);
        $supportingSheet = $group->sheets()->get()->first(fn ($item) => strtolower($item->name) === 'pendukung');
        $this->assertSame('pendukung', $supportingSheet->metadata['identity_color']);
        $this->assertSame('rank', $group->sheets()->where('name', 'Rank')->firstOrFail()->metadata['identity_color']);

        // Test Analisa
        $analisaComp = Livewire::actingAs($user)
            ->test(SpreadsheetWorksheet::class, ['sheet' => $analisaSheet]);
        $this->assertGreaterThanOrEqual(5, $analisaComp->get('freezeTopRowCount'));
        $this->assertGreaterThanOrEqual(2, $analisaComp->get('freezeLeftColumnCount'));

        $analisaHtml = $analisaComp->html();
        // Ensure no missing-semicolon CSS syntax error occurred
        $this->assertStringNotContainsString('solid #000000position:sticky', $analisaHtml);
        $this->assertStringNotContainsString('solid #000000 position:sticky', $analisaHtml);
        // Ensure sticky top and z-index 18 or 26 is rendered on data cells
        $this->assertStringContainsString('position:sticky;top:calc(var(--spreadsheet-column-header-height, 36px)', $analisaHtml);
        $this->assertStringContainsString('z-index:18', $analisaHtml);
        $this->assertStringContainsString('z-index:26', $analisaHtml);

        // Test Cost
        $costComp = Livewire::actingAs($user)
            ->test(SpreadsheetWorksheet::class, ['sheet' => $costSheet]);
        $this->assertSame(7, $costComp->get('freezeTopRowCount'));
        $this->assertGreaterThanOrEqual(2, $costComp->get('freezeLeftColumnCount'));
        $this->assertCount(7, $costComp->get('frozenRowTopOffsets'));
        $this->assertSame([], $costComp->get('fixedTopRowOffsets'));

        // Test Margin
        $marginComp = Livewire::actingAs($user)
            ->test(SpreadsheetWorksheet::class, ['sheet' => $marginSheet]);
        $this->assertGreaterThanOrEqual(4, $marginComp->get('freezeTopRowCount'));
        $this->assertGreaterThanOrEqual(2, $marginComp->get('freezeLeftColumnCount'));
    }

    public function test_sheet_identity_colors_cycle_and_survive_rename(): void
    {
        [$group] = $this->createSheet();
        $user = $group->creator;

        $expected = ['analisa', 'cost', 'margin', 'pendukung', 'rank'];
        foreach ($expected as $index => $identity) {
            $response = $this->actingAs($user)->post(route('groups.sheets.add', $group), [
                'name' => 'New Sheet ' . $index,
            ]);
            $response->assertRedirect();
            $sheet = $group->sheets()->where('name', 'New Sheet ' . $index)->firstOrFail();
            $this->assertSame($identity, $sheet->metadata['identity_color']);
        }

        $cycledSheet = $group->sheets()->where('name', 'New Sheet 0')->firstOrFail();
        $this->actingAs($user)->put(route('sheets.rename', $cycledSheet), ['name' => 'Renamed Sheet'])
            ->assertRedirect();
        $this->assertSame('analisa', $cycledSheet->fresh()->metadata['identity_color']);

        $nextSheet = $group->sheets()->create([
            'name' => 'After Cycle',
            'category' => 'custom',
            'metadata' => ['identity_color' => \App\Models\SpreadsheetSheet::cyclicIdentityColorKey(10)],
        ]);
        $this->assertSame('analisa', $nextSheet->identityColorKey());
    }

    private function createSheet(): array
    {
        $user = User::create(['name' => 'Spreadsheet owner', 'email' => uniqid('owner', true) . '@example.test', 'password' => 'password']);
        $group = FisheriesGroup::create([
            'name' => 'Sheet group', 'invite_code' => 'WS-TEST-01', 'location' => 'Utama',
            'created_by' => $user->id,
        ]);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $user->id, 'role' => 'leader', 'status' => 'Aktif']);
        $group->initializeStandardSheets();

        return [$group, $group->sheets()->firstOrFail()];
    }
}
