<?php


if (!function_exists('bill_money')) {
    function bill_money($v): string
    {
        return 'Rs. ' . number_format((float) $v, 2);
    }
}

if (!function_exists('bill_status')) {
    
    function bill_status(array $b): array
    {
        if (!empty($b['deleted_at']))  return ['cancelled', 'CANCELLED'];
        if (!empty($b['is_postponed'])) return ['postponed', 'POSTPONED'];
        if ((float) $b['balance'] <= 0) return ['paid', 'PAID'];
        if ((float) $b['paid'] > 0)     return ['partial', 'PARTIAL'];
        return ['pending', 'PENDING'];
    }
}

if (!function_exists('bill_action_form')) {
    
    function bill_action_form(string $action, int $id, string $label, string $class, string $confirm): string
    {
        return '<form method="POST" action="' . e($action) . '" class="inline-form" onsubmit="return confirm(' . e(json_encode($confirm)) . ')">'
            . '<input type="hidden" name="csrf" value="' . e(Auth::csrfToken()) . '">'
            . '<input type="hidden" name="id" value="' . $id . '">'
            . '<button type="submit" class="btn-mini ' . e($class) . '">' . e($label) . '</button></form>';
    }
}
