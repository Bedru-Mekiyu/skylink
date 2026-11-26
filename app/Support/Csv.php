<?php

namespace App\Support;

final class Csv
{
    /** Stop spreadsheet programs from running a cell that starts like a formula. */
    public static function row(array $cells): array
    {
        return array_map(fn ($v) => is_string($v) && preg_match('/^[=+\-@\t\r]/', $v) ? "'".$v : $v, $cells);
    }
}
