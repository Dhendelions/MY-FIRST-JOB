<?php

namespace App\Services;

use App\Models\FisheriesGroup;
use App\Models\SpreadsheetCell;
use App\Models\SpreadsheetSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SpreadsheetXlsxExporter
{
    public function createWorkbook(FisheriesGroup $group): Spreadsheet
    {
        $sheets = $group->sheets()->with(['columns', 'rows.cells.column'])->orderBy('id')->get();
        if ($sheets->isEmpty()) {
            throw new \RuntimeException('Workbook tidak memiliki worksheet untuk diekspor.');
        }

        $seenNames = [];
        foreach ($sheets as $sheet) {
            $normalizedName = mb_strtolower($sheet->name);
            if (isset($seenNames[$normalizedName])) {
                throw new \RuntimeException("Nama worksheet {$sheet->name} duplikat; ubah nama sebelum mengekspor.");
            }
            if (
                mb_strlen($sheet->name) > 31
                || preg_match('~[\\\\/?*:\[\]]~u', $sheet->name)
            ) {
                throw new \RuntimeException("Nama worksheet {$sheet->name} tidak valid untuk format XLSX.");
            }
            $seenNames[$normalizedName] = true;
        }

        $workbook = new Spreadsheet();
        $firstWorksheet = $workbook->getActiveSheet();
        $firstWorksheet->setTitle($sheets->first()->name);

        foreach ($sheets as $index => $sheet) {
            $worksheet = $index === 0 ? $firstWorksheet : $workbook->createSheet();
            if ($index > 0) {
                $worksheet->setTitle($sheet->name);
            }

            $this->writeSheet($worksheet, $sheet);
        }

        $workbook->setActiveSheetIndex(0);

        return $workbook;
    }

    public function saveWorkbook(FisheriesGroup $group, string $path): void
    {
        $sheets = $group->sheets()->with(['rows.cells.column'])->orderBy('id')->get();
        $writer = new Xlsx($this->createWorkbook($group));
        $writer->setPreCalculateFormulas(false);
        $writer->save($path);

        $archive = new \ZipArchive();
        if ($archive->open($path) !== true) {
            throw new \RuntimeException('File XLSX hasil ekspor tidak dapat dibuka untuk menyimpan nilai formula.');
        }

        try {
            $sheetEntries = $this->worksheetEntries($archive);
            foreach ($sheets as $sheet) {
                $entry = $sheetEntries[$sheet->name] ?? null;
                if (!$entry) {
                    throw new \RuntimeException("Worksheet {$sheet->name} tidak ditemukan dalam XLSX hasil ekspor.");
                }

                $xml = $archive->getFromName($entry);
                if ($xml === false) {
                    throw new \RuntimeException("Konten {$entry} tidak dapat dibaca dalam XLSX hasil ekspor.");
                }

                $document = new \DOMDocument();
                $document->loadXML($xml, LIBXML_NONET);
                $xpath = new \DOMXPath($document);
                $xpath->registerNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

                foreach ($sheet->rows as $row) {
                    foreach ($row->cells as $cell) {
                        if (!$cell->formula) {
                            continue;
                        }

                        $coordinate = $cell->column->letter . $row->sort_order;
                        $nodes = $xpath->query('//x:c[@r="' . $coordinate . '"]');
                        $cellNode = $nodes?->item(0);
                        if (!$cellNode instanceof \DOMElement) {
                            throw new \RuntimeException("Formula cell {$sheet->name}!{$coordinate} tidak ditemukan saat ekspor.");
                        }

                        $valueNodes = $xpath->query('./x:v', $cellNode);
                        $valueNode = $valueNodes?->item(0);
                        if (!$valueNode instanceof \DOMElement) {
                            $valueNode = $document->createElementNS(
                                'http://schemas.openxmlformats.org/spreadsheetml/2006/main',
                                'v'
                            );
                            $cellNode->appendChild($valueNode);
                        }

                        $cachedValue = (string) ($cell->value ?? '');
                        $valueNode->textContent = $cachedValue;
                        if (str_starts_with($cachedValue, '#')) {
                            $cellNode->setAttribute('t', 'e');
                        } elseif (is_numeric($cachedValue) || $cachedValue === '') {
                            $cellNode->removeAttribute('t');
                        } else {
                            $cellNode->setAttribute('t', 'str');
                        }
                    }
                }

                $archive->deleteName($entry);
                $archive->addFromString($entry, $document->saveXML());
            }
        } finally {
            $archive->close();
        }
    }

    private function writeSheet(Worksheet $worksheet, SpreadsheetSheet $sheet): void
    {
        foreach ($sheet->columns as $column) {
            $worksheet->getColumnDimension($column->letter)->setWidth(max(1, ($column->width - 5) / 7));
        }

        foreach ($sheet->rows as $row) {
            if ($row->height !== null) {
                $worksheet->getRowDimension($row->sort_order)->setRowHeight((float) $row->height);
            }

            foreach ($row->cells as $cell) {
                $coordinate = $cell->column->letter . $row->sort_order;
                $this->writeCell($worksheet, $coordinate, $cell);
            }
        }

        $metadata = $sheet->metadata ?? [];
        foreach ($metadata['merged_cells'] ?? [] as $range) {
            $worksheet->mergeCells($range);
        }
        if (!empty($metadata['freeze_pane'])) {
            $worksheet->freezePane($metadata['freeze_pane']);
        }
        if (array_key_exists('show_gridlines', $metadata)) {
            $worksheet->setShowGridlines((bool) $metadata['show_gridlines']);
        }
        if (isset($metadata['default_row_height']) && $metadata['default_row_height'] > 0) {
            $worksheet->getDefaultRowDimension()->setRowHeight((float) $metadata['default_row_height']);
        }
        if (isset($metadata['default_column_width']) && $metadata['default_column_width'] > 0) {
            $worksheet->getDefaultColumnDimension()->setWidth((float) $metadata['default_column_width']);
        }
    }

    private function writeCell(Worksheet $worksheet, string $coordinate, SpreadsheetCell $cell): void
    {
        $value = $cell->value ?? '';
        $dataType = $cell->format['xlsx_data_type'] ?? null;

        if ($cell->formula) {
            $worksheet->setCellValue($coordinate, $cell->formula);
            $worksheet->getCell($coordinate)->setCalculatedValue(
                $value,
                is_numeric($value)
            );
        } elseif ($dataType === DataType::TYPE_NUMERIC || ($dataType === null && is_numeric($value))) {
            $worksheet->setCellValueExplicit($coordinate, (float) $value, DataType::TYPE_NUMERIC);
        } else {
            $worksheet->setCellValueExplicit($coordinate, $value, DataType::TYPE_STRING);
        }

        $format = $cell->format ?? [];
        if (!empty($format['xlsx'])) {
            $worksheet->getStyle($coordinate)->applyFromArray($format['xlsx']);
        }

        $this->applyCustomFormatting($worksheet, $coordinate, $format);
    }

    private function applyCustomFormatting(Worksheet $worksheet, string $coordinate, array $format): void
    {
        $style = [];
        if (array_key_exists('bold', $format)) {
            $style['font']['bold'] = (bool) $format['bold'];
        }
        if (array_key_exists('italic', $format)) {
            $style['font']['italic'] = (bool) $format['italic'];
        }
        if (array_key_exists('underline', $format)) {
            $style['font']['underline'] = $format['underline'] ? 'single' : 'none';
        }
        if (!empty($format['color'])) {
            $style['font']['color']['rgb'] = ltrim($format['color'], '#');
        }
        if (!empty($format['bg'])) {
            $style['fill'] = [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => ltrim($format['bg'], '#')],
            ];
        }
        if (!empty($format['align'])) {
            $style['alignment']['horizontal'] = $format['align'];
        }
        if (isset($format['numFormat'])) {
            $style['numberFormat']['formatCode'] = match ($format['numFormat']) {
                'currency' => '"Rp" #,##0.00',
                'number' => '#,##0.00',
                'percent' => '0.00%',
                default => 'General',
            };
        }

        if ($style !== []) {
            $worksheet->getStyle($coordinate)->applyFromArray($style);
        }
    }

    private function worksheetEntries(\ZipArchive $archive): array
    {
        $workbookXml = $archive->getFromName('xl/workbook.xml');
        $relationshipsXml = $archive->getFromName('xl/_rels/workbook.xml.rels');
        if ($workbookXml === false || $relationshipsXml === false) {
            throw new \RuntimeException('Struktur worksheet tidak tersedia dalam XLSX hasil ekspor.');
        }

        $workbook = new \SimpleXMLElement($workbookXml);
        $workbook->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $workbook->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $relationships = new \SimpleXMLElement($relationshipsXml);
        $relationships->registerXPathNamespace(
            'p',
            'http://schemas.openxmlformats.org/package/2006/relationships'
        );
        $relationshipTargets = [];
        foreach ($relationships->xpath('//p:Relationship') ?: [] as $relationship) {
            $relationshipTargets[(string) $relationship['Id']] = (string) $relationship['Target'];
        }

        $entries = [];
        foreach ($workbook->xpath('//x:sheets/x:sheet') ?: [] as $sheet) {
            $relationshipId = (string) $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')->id;
            $target = $relationshipTargets[$relationshipId] ?? null;
            if ($target === null) {
                throw new \RuntimeException('Referensi worksheet tidak valid dalam XLSX hasil ekspor.');
            }

            $entries[(string) $sheet['name']] = str_starts_with($target, '/')
                ? ltrim($target, '/')
                : 'xl/' . ltrim($target, '/');
        }

        return $entries;
    }
}
