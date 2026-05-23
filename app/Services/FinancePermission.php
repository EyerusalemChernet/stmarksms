<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
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
                    'manage_fee_setup',
                    'delete_expenses',
                    'edit_invoices',
                    'apply_discounts',
                    'approve_fee_discount',
                    'approve_expenses',
                    'manage_discount_rules',
                    'manage_penalty_rules',
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
                    'edit_invoices',
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

    public static function requireAny(array $permissions): void
    {
        if (!self::hasAny($permissions)) {
            throw new HttpResponseException(
                redirect()->route('dashboard')->with(
                    'flash_danger',
                    'Access denied. You do not have permission for this action.'
                )
            );
        }
    }

    /** View fee categories & structures (accountant + super admin). */
    public static function canManageFeeSetup(): bool
    {
        return self::has('manage_fees') || self::has('manage_fee_setup');
    }

    /** Edit existing fee categories & structures (accountant + super admin). */
    public static function canEditFeeSetup(): bool
    {
        return self::canManageFeeSetup();
    }

    /** Create fee categories & structures (accountant + super admin). */
    public static function canCreateFeeSetup(): bool
    {
        return self::canManageFeeSetup();
    }

    /** Delete fee categories & structures (super admin only). */
    public static function canDeleteFeeSetup(): bool
    {
        return self::has('manage_fee_setup');
    }

    /** Edit one category/structure (super admin always; accountant only if not SA-locked). */
    public static function canEditFeeSetupRecord(?Model $record): bool
    {
        if (!$record || !self::canManageFeeSetup()) {
            return false;
        }

        if (self::has('manage_fee_setup')) {
            return true;
        }

        return !AdminFeeAudit::isLockedForAccountant($record);
    }

    public static function requireCanEditFeeSetupRecord(Model $record): void
    {
        if (!self::canEditFeeSetupRecord($record)) {
            throw new HttpResponseException(
                redirect()->back()->with(
                    'flash_danger',
                    'This item was created or updated by Super Admin. You can view it but cannot edit it.'
                )
            );
        }
    }

    /** Delete expense records (super admin only). */
    public static function canDeleteExpenses(): bool
    {
        return self::has('delete_expenses');
    }

    /** Delete expense categories (super admin only). */
    public static function canDeleteExpenseCategories(): bool
    {
        return self::has('delete_expenses');
    }

    public static function canEditExpense(\App\Models\Expense $expense): bool
    {
        if ($expense->isApproved()) {
            return false;
        }

        // Anyone can edit their own pending expense
        return $expense->isPending()
            && Auth::check()
            && (int) $expense->created_by === (int) Auth::id();
    }

    /** Admin & Super Admin: new expenses are approved immediately (accountant stays pending). */
    public static function autoApproveExpensesOnCreate(): bool
    {
        return false; // All roles submit as pending — admin/super_admin approve others' expenses
    }

    /** Record cash payment on invoices (accountant only). */
    public static function canRecordFeePayments(): bool
    {
        return Auth::check() && Auth::user()->user_type === 'accountant';
    }

    public static function requireRecordFeePayments(): void
    {
        if (!self::canRecordFeePayments()) {
            throw new HttpResponseException(
                redirect()->back()->with(
                    'flash_danger',
                    'Only the accountant can record cash payments.'
                )
            );
        }
    }

    /** Generate invoices & record payment on invoice detail (accountant + super admin). */
    public static function canEditInvoices(): bool
    {
        return self::has('manage_fees') || self::has('edit_invoices');
    }

    /** Payments list is read-only for super admin. */
    public static function isPaymentsViewOnly(): bool
    {
        return Auth::check()
            && Auth::user()->user_type === 'super_admin'
            && !self::has('manage_fees');
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
