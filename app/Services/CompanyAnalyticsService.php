<?php

namespace App\Services;

use App\Models\MonthlyWorkspace;
use App\Models\OperationalYear;
use App\Models\SpreadsheetCell;
use App\Models\SpreadsheetColumn;
use App\Models\SpreadsheetRow;
use App\Models\SpreadsheetSheet;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CompanyAnalyticsService
{
    /**
     * Get company-wide overview aggregating all operational years and selected year.
     */
    public function getCompanyOverview(?int $yearNumber = null): array
    {
        $allYears = OperationalYear::with(['monthlyWorkspaces.group.sheets'])
            ->orderBy('year', 'desc')
            ->get();

        if ($allYears->isEmpty()) {
            return [
                'has_years' => false,
                'all_years' => collect(),
                'selected_year' => null,
                'yearly_trends' => [],
                'financial_summary' => $this->emptyFinancialSummary(),
                'operational_summary' => $this->emptyOperationalSummary(),
                'monthly_breakdown' => [],
            ];
        }

        $selectedYear = $yearNumber 
            ? ($allYears->firstWhere('year', $yearNumber) ?? $allYears->first())
            : $allYears->first();

        // 1. Yearly trends across all registered years
        $yearlyTrends = $this->calculateYearlyTrends($allYears);

        // 2. Metrics for the selected year
        $yearMetrics = $this->calculateYearMetrics($selectedYear);

        return [
            'has_years' => true,
            'all_years' => $allYears,
            'selected_year' => $selectedYear,
            'yearly_trends' => $yearlyTrends,
            'financial_summary' => $yearMetrics['financial_summary'],
            'operational_summary' => $yearMetrics['operational_summary'],
            'monthly_breakdown' => $yearMetrics['monthly_breakdown'],
        ];
    }

    /**
     * Get detailed financial analytics for a specific operational year.
     */
    public function getFinancialAnalytics(OperationalYear $year): array
    {
        $yearMetrics = $this->calculateYearMetrics($year);

        return [
            'year' => $year->year,
            'is_archived' => $year->is_archived,
            'financial_summary' => $yearMetrics['financial_summary'],
            'monthly_breakdown' => $yearMetrics['monthly_breakdown'],
            'cost_composition' => $yearMetrics['cost_composition'],
        ];
    }

    /**
     * Get detailed operational analytics for a specific operational year.
     */
    public function getOperationalAnalytics(OperationalYear $year): array
    {
        $yearMetrics = $this->calculateYearMetrics($year);

        return [
            'year' => $year->year,
            'is_archived' => $year->is_archived,
            'operational_summary' => $yearMetrics['operational_summary'],
            'monthly_breakdown' => $yearMetrics['monthly_breakdown'],
            'utilities_and_supplies' => $yearMetrics['utilities_and_supplies'],
        ];
    }

    /**
     * Get multi-year comparative analytics.
     */
    public function getYearlyComparison(): array
    {
        $allYears = OperationalYear::with(['monthlyWorkspaces.group.sheets'])
            ->orderBy('year', 'asc')
            ->get();

        $comparison = [];
        foreach ($allYears as $year) {
            $metrics = $this->calculateYearMetrics($year);
            $comparison[] = [
                'year_id' => $year->id,
                'year' => $year->year,
                'is_archived' => $year->is_archived,
                'status' => $year->status,
                'workspaces_count' => $year->monthlyWorkspaces->count(),
                'financial' => $metrics['financial_summary'],
                'operational' => $metrics['operational_summary'],
            ];
        }

        return [
            'all_years' => $allYears,
            'comparison' => $comparison,
        ];
    }

    /**
     * Compute aggregated financial and operational metrics for an operational year.
     */
    public function calculateYearMetrics(OperationalYear $year): array
    {
        $workspaces = $year->monthlyWorkspaces()->with(['group.sheets'])->orderBy('month')->get();

        $totalOmset = 0.0;
        $totalRM = 0.0;
        $totalMargin = 0.0;
        $totalCost = 0.0;
        $totalPayroll = 0.0;
        $totalNett = 0.0;
        $totalBank = 0.0;

        $totalRawMaterialKg = 0.0;
        $totalFilletKg = 0.0;
        $totalFinishedGoodsKg = 0.0;
        $totalSideProductKg = 0.0;

        $costMC = 0.0;
        $costPB = 0.0;
        $costChemicals = 0.0;
        $costListrik = 0.0;
        $costAir = 0.0;
        $costEs = 0.0;
        $costSolar = 0.0;
        $costTransport = 0.0;
        $costColdStorage = 0.0;
        $costSewa = 0.0;

        $monthlyBreakdown = [];
        $hasAnyData = false;

        foreach ($workspaces as $mw) {
            $group = $mw->group;
            if (!$group) {
                continue;
            }

            $sheets = $group->sheets->keyBy('name');

            // 1. Extract from Sheet: Rank
            $rankSheet = $sheets->get('Rank') ?? $sheets->firstWhere(fn($s) => strtolower($s->name) === 'rank');
            $rankData = $rankSheet ? $this->extractRankSheetData($rankSheet) : [];

            $mOmset = $rankData['omset'] ?? 0.0;
            $mRM = $rankData['rm'] ?? 0.0;
            $mMargin = $rankData['margin'] ?? 0.0;
            $mCost = $rankData['cost'] ?? 0.0;
            $mPayroll = $rankData['payroll'] ?? 0.0;
            $mNett = $rankData['nett'] ?? 0.0;
            $mBank = $rankData['bank_total'] ?? 0.0;

            if ($mOmset > 0 || $mRM > 0 || $mCost > 0) {
                $hasAnyData = true;
            }

            $totalOmset += $mOmset;
            $totalRM += $mRM;
            $totalMargin += $mMargin;
            $totalCost += $mCost;
            $totalPayroll += $mPayroll;
            $totalNett += $mNett;
            $totalBank += $mBank;

            // 2. Extract from Sheet: Analisa
            $analisaSheet = $sheets->get('Analisa') ?? $sheets->firstWhere(fn($s) => strtolower($s->name) === 'analisa');
            $analisaData = $analisaSheet ? $this->extractAnalisaSheetData($analisaSheet) : [];

            $mRMKg = $analisaData['rm_kg'] ?? 0.0;
            $mFilletKg = $analisaData['fillet_kg'] ?? 0.0;
            $mFinishedGoodsKg = $analisaData['finished_goods_kg'] ?? 0.0;
            $mSideProductKg = $analisaData['side_product_kg'] ?? 0.0;

            $totalRawMaterialKg += $mRMKg;
            $totalFilletKg += $mFilletKg;
            $totalFinishedGoodsKg += $mFinishedGoodsKg;
            $totalSideProductKg += $mSideProductKg;

            // 3. Extract from Sheet: Cost
            $costSheet = $sheets->get('Cost') ?? $sheets->firstWhere(fn($s) => strtolower($s->name) === 'cost');
            $costData = $costSheet ? $this->extractCostSheetData($costSheet) : [];

            $costMC += $costData['mc'] ?? 0.0;
            $costPB += $costData['pb'] ?? 0.0;
            $costChemicals += $costData['chemicals'] ?? 0.0;
            $costListrik += $costData['listrik'] ?? 0.0;
            $costAir += $costData['air'] ?? 0.0;
            $costEs += $costData['es'] ?? 0.0;
            $costSolar += $costData['solar'] ?? 0.0;
            $costTransport += $costData['transport'] ?? 0.0;
            $costColdStorage += $costData['cold_storage'] ?? 0.0;
            $costSewa += $costData['sewa'] ?? 0.0;

            $monthlyBreakdown[] = [
                'workspace_id' => $mw->id,
                'group_id' => $group->id,
                'month' => $mw->month,
                'name' => $mw->name,
                'has_data' => ($mOmset > 0 || $mRM > 0 || $mCost > 0 || $mRMKg > 0),
                'financial' => [
                    'omset' => $mOmset,
                    'rm' => $mRM,
                    'margin' => $mMargin,
                    'cost' => $mCost,
                    'payroll' => $mPayroll,
                    'nett' => $mNett,
                    'bank' => $mBank,
                ],
                'operational' => [
                    'rm_kg' => $mRMKg,
                    'fillet_kg' => $mFilletKg,
                    'finished_goods_kg' => $mFinishedGoodsKg,
                    'rendemen_fillet_pct' => $mRMKg > 0 ? round(($mFilletKg / $mRMKg) * 100, 2) : 0.0,
                ],
            ];
        }

        // Avoid division by zero
        $marginPct = $totalOmset > 0 ? round(($totalMargin / $totalOmset) * 100, 2) : 0.0;
        $netProfitMarginPct = $totalOmset > 0 ? round(($totalNett / $totalOmset) * 100, 2) : 0.0;
        $rendemenFilletPct = $totalRawMaterialKg > 0 ? round(($totalFilletKg / $totalRawMaterialKg) * 100, 2) : 0.0;
        $totalRendemenPct = $totalRawMaterialKg > 0 ? round((($totalFinishedGoodsKg + $totalSideProductKg) / $totalRawMaterialKg) * 100, 2) : 0.0;

        return [
            'has_data' => $hasAnyData,
            'financial_summary' => [
                'total_omset' => $totalOmset,
                'total_rm' => $totalRM,
                'total_margin' => $totalMargin,
                'margin_pct' => $marginPct,
                'total_cost' => $totalCost,
                'total_payroll' => $totalPayroll,
                'total_nett' => $totalNett,
                'net_profit_margin_pct' => $netProfitMarginPct,
                'total_bank' => $totalBank,
            ],
            'operational_summary' => [
                'total_rm_kg' => $totalRawMaterialKg,
                'total_fillet_kg' => $totalFilletKg,
                'rendemen_fillet_pct' => $rendemenFilletPct,
                'total_finished_goods_kg' => $totalFinishedGoodsKg,
                'total_side_product_kg' => $totalSideProductKg,
                'total_rendemen_pct' => $totalRendemenPct,
            ],
            'cost_composition' => [
                'raw_materials' => $totalRM,
                'operational_cost' => $totalCost,
                'payroll' => $totalPayroll,
                'total' => $totalRM + $totalCost + $totalPayroll,
            ],
            'utilities_and_supplies' => [
                'packaging_mc' => $costMC,
                'packaging_pb' => $costPB,
                'chemicals' => $costChemicals,
                'listrik' => $costListrik,
                'air' => $costAir,
                'es' => $costEs,
                'solar' => $costSolar,
                'transport' => $costTransport,
                'cold_storage' => $costColdStorage,
                'sewa' => $costSewa,
            ],
            'monthly_breakdown' => $monthlyBreakdown,
        ];
    }

    /**
     * Extract key financial metrics from the Rank sheet without loading the entire grid.
     */
    private function extractRankSheetData(SpreadsheetSheet $sheet): array
    {
        // Query only column C (Total) for rows 2, 3, 4, 5, 6, 7, 13
        $colC = SpreadsheetColumn::where('sheet_id', $sheet->id)->where('letter', 'C')->first();
        if (!$colC) {
            return [];
        }

        $targetRows = SpreadsheetRow::where('sheet_id', $sheet->id)
            ->whereIn('sort_order', [2, 3, 4, 5, 6, 7, 10, 11, 13])
            ->pluck('id', 'sort_order');

        if ($targetRows->isEmpty()) {
            return [];
        }

        $cells = SpreadsheetCell::whereIn('row_id', $targetRows->values())
            ->where('column_id', $colC->id)
            ->get()
            ->keyBy('row_id');

        $getCellVal = function (int $sortOrder) use ($targetRows, $cells): float {
            $rowId = $targetRows[$sortOrder] ?? null;
            if (!$rowId) return 0.0;
            $cell = $cells->get($rowId);
            if (!$cell || $cell->value === null || $cell->value === '') return 0.0;
            return (float) preg_replace('/[^0-9.\-]/', '', (string)$cell->value);
        };

        return [
            'omset' => $getCellVal(2),
            'rm' => $getCellVal(3),
            'margin' => $getCellVal(4),
            'cost' => $getCellVal(5),
            'payroll' => $getCellVal(6),
            'nett' => $getCellVal(7),
            'bri' => $getCellVal(10),
            'bni' => $getCellVal(11),
            'bank_total' => $getCellVal(13),
        ];
    }

    /**
     * Extract production metrics from the Analisa sheet.
     */
    private function extractAnalisaSheetData(SpreadsheetSheet $sheet): array
    {
        // Analisa:
        // Row 4: RM input kg
        // Row 5: Rendemen fillet kg
        // Row 17: Finished goods kg
        // Row 23: Side product kg
        $targetRows = SpreadsheetRow::where('sheet_id', $sheet->id)
            ->whereIn('sort_order', [4, 5, 17, 23])
            ->pluck('id', 'sort_order');

        if ($targetRows->isEmpty()) {
            return [];
        }

        // Cells in rows 4, 5, 17, 23 across all numeric columns
        $cells = SpreadsheetCell::whereIn('row_id', $targetRows->values())
            ->whereNotNull('value')
            ->where('value', '!=', '')
            ->get();

        $sumNumericRow = function (int $sortOrder) use ($targetRows, $cells): float {
            $rowId = $targetRows[$sortOrder] ?? null;
            if (!$rowId) return 0.0;
            $rowCells = $cells->where('row_id', $rowId);
            $sum = 0.0;
            foreach ($rowCells as $c) {
                $val = preg_replace('/[^0-9.\-]/', '', (string)$c->value);
                if (is_numeric($val) && (float)$val > 0) {
                    $sum += (float)$val;
                }
            }
            return $sum;
        };

        return [
            'rm_kg' => $sumNumericRow(4),
            'fillet_kg' => $sumNumericRow(5),
            'finished_goods_kg' => $sumNumericRow(17),
            'side_product_kg' => $sumNumericRow(23),
        ];
    }

    /**
     * Extract itemized cost categories from the Cost sheet.
     */
    private function extractCostSheetData(SpreadsheetSheet $sheet): array
    {
        // Cost sheet row mapping:
        // Row 8: MC Ready
        // Row 9: PB Ready
        // Row 11: Non Phospate
        // Row 12: Pembersih
        // Row 13: Listrik
        // Row 14: Air
        // Row 15: Es
        // Row 16: Solar
        // Row 17: Transport
        // Row 18: Cold Storage
        // Row 19: Sewa
        $targetRows = SpreadsheetRow::where('sheet_id', $sheet->id)
            ->whereIn('sort_order', [8, 9, 11, 12, 13, 14, 15, 16, 17, 18, 19])
            ->pluck('id', 'sort_order');

        if ($targetRows->isEmpty()) {
            return [];
        }

        $cells = SpreadsheetCell::whereIn('row_id', $targetRows->values())
            ->whereNotNull('value')
            ->where('value', '!=', '')
            ->get();

        $sumCostRow = function (int $sortOrder) use ($targetRows, $cells): float {
            $rowId = $targetRows[$sortOrder] ?? null;
            if (!$rowId) return 0.0;
            $rowCells = $cells->where('row_id', $rowId);
            $sum = 0.0;
            foreach ($rowCells as $c) {
                $val = preg_replace('/[^0-9.\-]/', '', (string)$c->value);
                if (is_numeric($val) && (float)$val > 0) {
                    $sum += (float)$val;
                }
            }
            return $sum;
        };

        return [
            'mc' => $sumCostRow(8),
            'pb' => $sumCostRow(9),
            'chemicals' => $sumCostRow(11) + $sumCostRow(12),
            'listrik' => $sumCostRow(13),
            'air' => $sumCostRow(14),
            'es' => $sumCostRow(15),
            'solar' => $sumCostRow(16),
            'transport' => $sumCostRow(17),
            'cold_storage' => $sumCostRow(18),
            'sewa' => $sumCostRow(19),
        ];
    }

    /**
     * Calculate yearly trends across all registered operational years.
     */
    private function calculateYearlyTrends(Collection $allYears): array
    {
        $trends = [];
        foreach ($allYears as $year) {
            $metrics = $this->calculateYearMetrics($year);
            $trends[] = [
                'year' => $year->year,
                'is_archived' => $year->is_archived,
                'omset' => $metrics['financial_summary']['total_omset'],
                'cost' => $metrics['financial_summary']['total_cost'],
                'nett' => $metrics['financial_summary']['total_nett'],
                'margin_pct' => $metrics['financial_summary']['margin_pct'],
                'rm_kg' => $metrics['operational_summary']['total_rm_kg'],
            ];
        }

        return $trends;
    }

    private function emptyFinancialSummary(): array
    {
        return [
            'total_omset' => 0.0,
            'total_rm' => 0.0,
            'total_margin' => 0.0,
            'margin_pct' => 0.0,
            'total_cost' => 0.0,
            'total_payroll' => 0.0,
            'total_nett' => 0.0,
            'net_profit_margin_pct' => 0.0,
            'total_bank' => 0.0,
        ];
    }

    private function emptyOperationalSummary(): array
    {
        return [
            'total_rm_kg' => 0.0,
            'total_fillet_kg' => 0.0,
            'rendemen_fillet_pct' => 0.0,
            'total_finished_goods_kg' => 0.0,
            'total_side_product_kg' => 0.0,
            'total_rendemen_pct' => 0.0,
        ];
    }
}

