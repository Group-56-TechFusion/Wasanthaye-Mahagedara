<?php
if (!function_exists('rep_money')) {
    function rep_money($n): string
    {
        return 'Rs. ' . number_format((float) $n, 2);
    }

    /** Render one table cell for a drill-down column type. */
    function rep_cell(string $type, $value): string
    {
        switch ($type) {
            case 'money':
                return '<td class="num">' . e(rep_money($value)) . '</td>';
            case 'bold':
                return '<td><strong>' . e((string) $value) . '</strong></td>';
            case 'tag':
                return '<td><span class="tag">' . e((string) $value) . '</span></td>';
            case 'date':
                return '<td>' . ($value ? e((string) $value) : '<span class="muted">&mdash;</span>') . '</td>';
            case 'status':
                return '<td><span class="badge-status ' . e(strtolower((string) $value)) . '">' . e((string) $value) . '</span></td>';
            default:
                return '<td>' . e((string) $value) . '</td>';
        }
    }
}
