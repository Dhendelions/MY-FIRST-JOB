<?php

namespace App\Services;

class SpreadsheetFormulaEngine
{
    /**
     * Check if a given string is a spreadsheet formula.
     */
    public static function isFormula(?string $value): bool
    {
        return $value !== null && str_starts_with(trim($value), '=');
    }

    /**
     * Validate formula syntax.
     * Returns null if valid, or an error description string if invalid.
     */
    public static function validateFormulaSyntax(?string $formula): ?string
    {
        if ($formula === null) {
            return null;
        }

        $raw = trim($formula);
        if (!str_starts_with($raw, '=')) {
            return null;
        }

        $expr = trim(substr($raw, 1));
        if ($expr === '') {
            return 'Formula tidak boleh kosong setelah tanda sama dengan (=).';
        }

        if (substr_count($raw, '(') !== substr_count($raw, ')')) {
            return 'Jumlah tanda kurung buka "(" dan tutup ")" tidak seimbang.';
        }

        if (preg_match('/[+\-*\/]$/', $expr)) {
            return 'Formula berakhir dengan operator aritmatika yang tidak lengkap.';
        }

        if (preg_match('/[+\-*\/]{2,}/', $expr, $m)) {
            if (!preg_match('/^[+\-*\/][\-]$/', $m[0])) {
                return 'Terdapat operator berurutan yang tidak valid: "' . $m[0] . '"';
            }
        }

        try {
            $dummy = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $ws = $dummy->getActiveSheet();
            $ws->setCellValue('A1', 1);
            $ws->setCellValue('B1', 2);
            $ws->setCellValue('A2', $raw);
            $ws->getCell('A2')->getCalculatedValue();
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'Formula Error:') || str_contains($msg, 'syntax error') || str_contains($msg, 'Parse error')) {
                $cleaned = preg_replace('/^.*?Formula Error:\s*/i', '', $msg);
                return 'Sintaks formula salah: ' . $cleaned;
            }
        }

        return null;
    }


    /**
     * Parse and evaluate a formula string against a cell coordinate grid.
     * $grid is an associative array like ['A1' => 10, 'B1' => 25, 'C1' => 'Text'].
     *
     * @param string $formula
     * @param array<string, mixed> $grid Keyed by uppercase coord (e.g. 'A1', 'B2')
     * @return string Calculated value or error code (#VALUE!, #DIV/0!, #REF!)
     */
    public static function evaluate(string $formula, array $grid): string
    {
        $raw = trim($formula);
        if (!str_starts_with($raw, '=')) {
            return $raw;
        }

        $expr = trim(substr($raw, 1));
        if ($expr === '') {
            return '';
        }

        // Standardize grid keys to uppercase
        $normalizedGrid = [];
        foreach ($grid as $coord => $val) {
            $normalizedGrid[strtoupper(trim($coord))] = $val;
        }

        try {
            // Check for built-in functions: SUM, AVERAGE, MIN, MAX, COUNT
            if (preg_match('/^([A-Z]+)\s*\((.*)\)$/i', $expr, $matches)) {
                $func = strtoupper($matches[1]);
                $argsContent = trim($matches[2]);

                return self::executeFunction($func, $argsContent, $normalizedGrid);
            }

            // Resolve any functions embedded within arithmetic expressions (e.g. SUM(C8:C15)-C16)
            $expr = self::resolveEmbeddedFunctions($expr, $normalizedGrid);

            // Otherwise evaluate arithmetic expression (+, -, *, /, %, cell refs, numbers)
            return self::evaluateArithmetic($expr, $normalizedGrid);
        } catch (\DivisionByZeroError | \Throwable $e) {
            if (str_contains($e->getMessage(), 'Division by zero') || $e instanceof \DivisionByZeroError) {
                return '#DIV/0!';
            }
            return '#VALUE!';
        }
    }

    /**
     * Resolve embedded functions like SUM(A1:A5) within compound expressions.
     */
    protected static function resolveEmbeddedFunctions(string $expr, array $grid): string
    {
        $iterations = 0;
        while ($iterations++ < 10 && preg_match('/([A-Z]+)\s*\(([^()]*)\)/i', $expr, $matches)) {
            $func = strtoupper($matches[1]);
            if (!in_array($func, ['SUM', 'AVERAGE', 'AVG', 'MIN', 'MAX', 'COUNT'], true)) {
                break;
            }
            $val = self::executeFunction($func, $matches[2], $grid);
            $expr = str_replace($matches[0], $val, $expr);
        }

        return $expr;
    }

    /**
     * Execute built-in spreadsheet functions.
     */
    protected static function executeFunction(string $func, string $argsStr, array $grid): string
    {
        $values = self::resolveArgumentsToNumbers($argsStr, $grid);

        switch ($func) {
            case 'SUM':
                return (string) array_sum($values);

            case 'AVERAGE':
            case 'AVG':
                $count = count($values);
                if ($count === 0) {
                    return '0';
                }
                $avg = array_sum($values) / $count;
                return (string) round($avg, 4);

            case 'MIN':
                if (empty($values)) {
                    return '0';
                }
                return (string) min($values);

            case 'MAX':
                if (empty($values)) {
                    return '0';
                }
                return (string) max($values);

            case 'COUNT':
                return (string) count($values);

            default:
                return '#NAME?';
        }
    }

    /**
     * Resolve argument string containing comma-separated ranges or cell references to a flat array of numbers.
     * e.g. "A1:A5, B1, 10"
     */
    protected static function resolveArgumentsToNumbers(string $argsStr, array $grid): array
    {
        $tokens = array_map('trim', explode(',', $argsStr));
        $numbers = [];

        foreach ($tokens as $token) {
            if ($token === '') continue;

            // Check if it's a range like A1:A10 or B2:D5
            if (preg_match('/^([A-Z]+[0-9]+):([A-Z]+[0-9]+)$/i', $token, $m)) {
                $coords = self::expandRange($m[1], $m[2]);
                foreach ($coords as $coord) {
                    $rawVal = $grid[strtoupper($coord)] ?? null;
                    $num = self::extractNumber($rawVal);
                    if ($num !== null) {
                        $numbers[] = $num;
                    }
                }
            } elseif (preg_match('/^[A-Z]+[0-9]+$/i', $token)) {
                // Single cell reference
                $rawVal = $grid[strtoupper($token)] ?? null;
                $num = self::extractNumber($rawVal);
                if ($num !== null) {
                    $numbers[] = $num;
                }
            } elseif (is_numeric($token)) {
                $numbers[] = (float) $token;
            }
        }

        return $numbers;
    }

    /**
     * Expand range e.g. A1 to B3 into ['A1', 'A2', 'A3', 'B1', 'B2', 'B3'].
     */
    public static function expandRange(string $start, string $end): array
    {
        preg_match('/^([A-Z]+)([0-9]+)$/i', strtoupper($start), $mStart);
        preg_match('/^([A-Z]+)([0-9]+)$/i', strtoupper($end), $mEnd);

        if (!$mStart || !$mEnd) {
            return [];
        }

        $colStart = $mStart[1];
        $rowStart = (int) $mStart[2];
        $colEnd = $mEnd[1];
        $rowEnd = (int) $mEnd[2];

        // Determine column range
        $colStartIdx = self::columnLetterToIndex($colStart);
        $colEndIdx = self::columnLetterToIndex($colEnd);

        $minCol = min($colStartIdx, $colEndIdx);
        $maxCol = max($colStartIdx, $colEndIdx);
        $minRow = min($rowStart, $rowEnd);
        $maxRow = max($rowStart, $rowEnd);

        $coords = [];
        for ($c = $minCol; $c <= $maxCol; $c++) {
            $letter = self::indexToColumnLetter($c);
            for ($r = $minRow; $r <= $maxRow; $r++) {
                $coords[] = "{$letter}{$r}";
            }
        }

        return $coords;
    }

    /**
     * Convert column letter (A, B, AA) to 1-based index.
     */
    public static function columnLetterToIndex(string $letter): int
    {
        $letter = strtoupper($letter);
        $len = strlen($letter);
        $index = 0;
        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($letter[$i]) - ord('A') + 1);
        }
        return $index;
    }

    /**
     * Convert 1-based index to column letter (1 -> A, 27 -> AA).
     */
    public static function indexToColumnLetter(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $rem = ($index - 1) % 26;
            $letter = chr(65 + $rem) . $letter;
            $index = (int) (($index - $rem) / 26);
        }
        return $letter;
    }

    /**
     * Extract float from cell value or return null if non-numeric.
     * Supports standard numbers as well as Indonesian currency (Rp 1.000.000,50).
     */
    public static function extractNumber(mixed $val): ?float
    {
        if ($val === null || $val === '') {
            return null;
        }

        if (is_numeric($val)) {
            return (float) $val;
        }

        if (is_string($val)) {
            $clean = trim(preg_replace('/[^\d\.\,\-]/', '', $val));
            if ($clean === '' || $clean === '-') {
                return null;
            }

            // If string has both dot and comma e.g. "1.234.567,89" or "1,234,567.89"
            if (str_contains($clean, '.') && str_contains($clean, ',')) {
                $lastDot = strrpos($clean, '.');
                $lastComma = strrpos($clean, ',');
                if ($lastComma > $lastDot) {
                    // Indonesian / European style: 1.000,50 -> remove dots, replace comma with dot
                    $clean = str_replace('.', '', $clean);
                    $clean = str_replace(',', '.', $clean);
                } else {
                    // US / English style: 1,000.50 -> remove commas
                    $clean = str_replace(',', '', $clean);
                }
            } elseif (substr_count($clean, '.') > 1) {
                // Multiple dots e.g. "1.000.000" -> thousands separators
                $clean = str_replace('.', '', $clean);
            } elseif (substr_count($clean, ',') > 1) {
                // Multiple commas e.g. "1,000,000" -> thousands separators
                $clean = str_replace(',', '', $clean);
            } elseif (str_contains($clean, '.')) {
                // Single dot: e.g. "500.000" (thousands) vs "10.5" (decimal)
                $parts = explode('.', $clean);
                if (strlen($parts[1]) === 3 && !str_starts_with($clean, '0.')) {
                    // Indonesian thousands separator e.g. "500.000"
                    $clean = str_replace('.', '', $clean);
                }
            } elseif (str_contains($clean, ',')) {
                // Single comma: in Indonesian usually decimal separator e.g. "10,5"
                $parts = explode(',', $clean);
                if (strlen($parts[1]) === 3 && !str_starts_with($clean, '0,')) {
                    // English thousands separator e.g. "500,000"
                    $clean = str_replace(',', '', $clean);
                } else {
                    $clean = str_replace(',', '.', $clean);
                }
            }

            if (is_numeric($clean)) {
                return (float) $clean;
            }
        }

        return null;
    }

    /**
     * Safe arithmetic parser for expressions like "A1 + B2 * 3 - (C1 / 2)".
     * Uses safe shunting-yard algorithm and RPN evaluation - NO eval()!
     */
    protected static function evaluateArithmetic(string $expr, array $grid): string
    {
        // 1. Tokenize expression into numbers, cell references (with optional $), operators, and %
        $pattern = '/(\$?[A-Z]+\$?[0-9]+|\d+\.?\d*|\+|\-|\*|\/|\(|\)|%)/i';
        preg_match_all($pattern, $expr, $matches);
        $tokens = $matches[0];

        if (empty($tokens)) {
            return '';
        }

        // 2. Replace cell references with numbers
        $cleanTokens = [];
        foreach ($tokens as $token) {
            if (preg_match('/^\$?([A-Z]+)\$?([0-9]+)$/i', $token, $m)) {
                $coord = strtoupper($m[1] . $m[2]);
                $rawVal = $grid[$coord] ?? 0;
                $num = self::extractNumber($rawVal) ?? 0.0;
                $cleanTokens[] = (string) $num;
            } else {
                $cleanTokens[] = $token;
            }
        }

        // 3. Shunting-Yard Algorithm to produce Reverse Polish Notation (RPN)
        $outputQueue = [];
        $operatorStack = [];
        $precedence = ['+' => 1, '-' => 1, '*' => 2, '/' => 2, '%' => 3];

        $prevToken = null;
        foreach ($cleanTokens as $token) {
            if (is_numeric($token)) {
                $outputQueue[] = (float) $token;
            } elseif ($token === '%') {
                // Postfix % operator immediately applies to top of output queue
                if (!empty($outputQueue)) {
                    $last = array_pop($outputQueue);
                    $outputQueue[] = $last * 0.01;
                }
            } elseif (isset($precedence[$token])) {
                // Handle unary minus
                if ($token === '-' && ($prevToken === null || in_array($prevToken, ['+', '-', '*', '/', '(', '%'], true))) {
                    $token = 'u-';
                }

                $prec = $token === 'u-' ? 4 : $precedence[$token];
                while (!empty($operatorStack)) {
                    $top = end($operatorStack);
                    $topPrec = $top === 'u-' ? 4 : ($precedence[$top] ?? 0);
                    if ($topPrec >= $prec) {
                        $outputQueue[] = array_pop($operatorStack);
                    } else {
                        break;
                    }
                }
                $operatorStack[] = $token;
            } elseif ($token === '(') {
                $operatorStack[] = $token;
            } elseif ($token === ')') {
                while (!empty($operatorStack) && end($operatorStack) !== '(') {
                    $outputQueue[] = array_pop($operatorStack);
                }
                if (!empty($operatorStack) && end($operatorStack) === '(') {
                    array_pop($operatorStack);
                }
            }
            $prevToken = $token;
        }

        while (!empty($operatorStack)) {
            $outputQueue[] = array_pop($operatorStack);
        }

        // 4. Evaluate RPN Stack safely
        $evalStack = [];
        foreach ($outputQueue as $token) {
            if (is_numeric($token)) {
                $evalStack[] = (float) $token;
            } elseif ($token === 'u-') {
                $operand = array_pop($evalStack) ?? 0;
                $evalStack[] = -$operand;
            } elseif (in_array($token, ['+', '-', '*', '/'])) {
                $b = array_pop($evalStack) ?? 0;
                $a = array_pop($evalStack) ?? 0;

                switch ($token) {
                    case '+': $evalStack[] = $a + $b; break;
                    case '-': $evalStack[] = $a - $b; break;
                    case '*': $evalStack[] = $a * $b; break;
                    case '/':
                        if ($b == 0) {
                            return '0';
                        }
                        $evalStack[] = $a / $b;
                        break;
                }
            }
        }

        if (empty($evalStack)) {
            return '0';
        }

        $result = end($evalStack);
        // Format neatly: if integer, show without decimal, else round to 4 decimals
        if (floor($result) == $result) {
            return (string) (int) $result;
        }
        return (string) round($result, 4);
    }
}
