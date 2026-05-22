<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;

class FinancePermission
{
    private static function financeRoles(): array
    {
        return ['accountant', 'admin', 'super_admin'];
    }

    private static function permissionsForRole(string $role): array
    {
        $view = [
            'view_invoices',
            'view_discount_rules',
            'view_penalty_rules',
            'view_reports',
        ];

        switch ($role) {
            case 'super_admin':
                return array_merge($view, [
                    'apply_discounts',
                    'approve_fee_discount',
                    'approve_expenses',
                    'manage_discount_rules',
                    'manage_penalty_rules',
                    'manage_fees',
                    'manage_expenses',
                    'manage_income',
                    'manage_transport',
                    'manage_settings',
                ]);

            case 'admin':
                return array_merge($view, [
                    'apply_discounts',
                    'approve_fee_discount',
                    'approve_expenses',
                    'manage_fees',
                    'manage_expenses',
                    'manage_income',
                    'manage_transport',
                    'manage_settings',
                ]);

            case 'accountant':
                return array_merge($view, [
                    'apply_discounts',
                    'manage_fees',
                    'manage_expenses',
                    'manage_income',
                    'manage_transport',
                ]);

            default:
                return [];
        }
    }

    public static function has(string $permission): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $role = Auth::user()->user_type;
        if (!in_array($role, self::financeRoles(), true)) {
            return false;
        }

        return in_array($permission, self::permissionsForRole($role), true);
    }

    public static function hasAny(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (self::has($permission)) {
                return true;
            }
        }

        return false;
    }

    public static function require(string $permission): void
    {
        if (!self::has($permission)) {
            throw new HttpResponseException(
                redirect()->route('dashboard')->with(
                    'flash_danger',
                    'Access denied. You do not have permission for this action.'
                )
            );
        }
    }

    public static function audit(
        string $action,
        string $module,
        ?string $modelClass = null,
        $recordId = null,
        array $before = [],
        array $after = []
    ): void {
        if (!Auth::check()) {
            return;
        }

        $parts = [$action, $module];
        if ($recordId !== null) {
            $parts[] = '#' . $recordId;
        }
        if ($modelClass) {
            $parts[] = class_basename($modelClass);
        }

        $description = implode(' ', $parts);
        if (!empty($after)) {
            $description .= ' — ' . json_encode($after);
        }

        try {
            AuditLog::log($action, $module, $description);
        } catch (\Throwable $e) {
            // Non-blocking
        }
    }
}
