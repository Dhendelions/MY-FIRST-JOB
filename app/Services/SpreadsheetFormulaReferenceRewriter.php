<?php

namespace App\Services;

class SpreadsheetFormulaReferenceRewriter
{
    public function rewrite(string $formula, array $sheetNameMap): string
    {
        $nameMap = [];
        foreach ($sheetNameMap as $source => $target) {
            $nameMap[mb_strtolower($source)] = $target;
        }

        $formula = preg_replace_callback(
            "/'((?:[^']|'')+)'!/",
            function (array $matches) use ($nameMap) {
                $source = mb_strtolower(str_replace("''", "'", $matches[1]));
                if (!isset($nameMap[$source])) {
                    return $matches[0];
                }

                return $this->quoteSheetReference($nameMap[$source]) . '!';
            },
            $formula
        );

        return preg_replace_callback(
            '/(?<![\w\]])\b([A-Za-z_][A-Za-z0-9_.]*)!/',
            function (array $matches) use ($nameMap) {
                $source = mb_strtolower($matches[1]);
                if (!isset($nameMap[$source])) {
                    return $matches[0];
                }

                return $this->quoteSheetReference($nameMap[$source]) . '!';
            },
            $formula
        );
    }

    private function quoteSheetReference(string $name): string
    {
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_.]*$/', $name)) {
            return $name;
        }

        return "'" . str_replace("'", "''", $name) . "'";
    }
}
