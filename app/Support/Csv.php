<?php

namespace App\Support;

class Csv
{
    /**
     * Values may come from the public, so a cell like `=HYPERLINK(...)` must
     * not run as a formula when an admin opens the file in a spreadsheet.
     */
    public static function cell(mixed $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }
}
