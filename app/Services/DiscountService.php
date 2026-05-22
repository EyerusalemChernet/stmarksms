<?php

namespace App\Services;

use App\Helpers\Qs;
use App\Models\Employee;
use App\Models\Setting;
use App\Models\StudentFeeInvoice;
use App\Models\StudentRecord;
use App\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DiscountService
{
    public const SETTING_SIBLING  = 'sibling_discount_pct';
    public const SETTING_EMPLOYEE = 'employee_discount_pct';

    /** Staff user types — parent with this type qualifies for employee-child discount */
    private const STAFF_TYPES = ['teacher', 'hr_manager', 'admin', 'super_admin'];

    public static function getSiblingPct(): float
    {
        return (float) (Qs::getSetting(self::SETTING_SIBLING) ?: 10);
    }

    public static function getEmployeePct(): float
    {
        return (float) (Qs::getSetting(self::SETTING_EMPLOYEE) ?: 15);
    }

    public static function setDiscountRules(float $siblingPct, float $employeePct): void
    {
        Setting::updateOrCreate(
            ['type' => self::SETTING_SIBLING],
            ['description' => (string) $siblingPct]
        );
        Setting::updateOrCreate(
            ['type' => self::SETTING_EMPLOYEE],
            ['description' => (string) $employeePct]
        );
    }

    /**
     * Parent has 2+ active enrolled children → sibling discount applies.
     */
    public static function isSiblingEligible(StudentRecord $student): bool
    {
        if (!$student->my_parent_id || $student->grad) {
            return false;
        }

        return StudentRecord::where('my_parent_id', $student->my_parent_id)
            ->where('grad', 0)
            ->count() >= 2;
    }

    /**
     * Parent/guardian is school staff → employee-child discount applies.
     */
    public static function isEmployeeChildEligible(StudentRecord $student): bool
    {
        if (!$student->my_parent_id || $student->grad) {
            return false;
        }

        $parent = User::find($student->my_parent_id);
        if (!$parent) {
            return false;
        }

        if (in_array($parent->user_type, self::STAFF_TYPES, true)) {
            return true;
        }

        if (Employee::where('user_id', $parent->id)->where('status', 'active')->exists()) {
            return true;
        }

        if ($parent->email) {
            return Employee::where('email', $parent->email)->where('status', 'active')->exists();
        }

        return false;
    }

    public static function getDiscountTypeForStudent(StudentRecord $student): ?string
    {
        if (self::isEmployeeChildEligible($student)) {
            return 'employee_child';
        }
        if (self::isSiblingEligible($student)) {
            return 'sibling';
        }

        return null;
    }

    public static function calculateDiscountAmount(float $originalAmount, string $type): float
    {
        $pct = $type === 'employee_child'
            ? self::getEmployeePct()
            : self::getSiblingPct();

        return round($originalAmount * ($pct / 100), 2);
    }

    /**
     * Summary for discount rules page (eligible families / students).
     */
    public static function getEligibleStudentsSummary(): array
    {
        $active = StudentRecord::with(['user', 'my_class', 'my_parent'])
            ->where('grad', 0)
            ->get();

        $siblingStudents = $active->filter(fn ($sr) => self::isSiblingEligible($sr));
        $employeeStudents = $active->filter(fn ($sr) => self::isEmployeeChildEligible($sr));

        $siblingFamilies = $siblingStudents
            ->groupBy('my_parent_id')
            ->map(function (Collection $children) {
                $parent = $children->first()->my_parent;
                return [
                    'parent_name' => $parent->name ?? 'Unknown',
                    'children'    => $children->map(fn ($sr) => [
                        'name'  => $sr->user->name ?? '-',
                        'class' => $sr->my_class->name ?? '-',
                        'adm'   => $sr->adm_no ?? '-',
                    ])->values()->all(),
                    'count'       => $children->count(),
                ];
            })
            ->values();

        $employeeChildren = $employeeStudents->map(function ($sr) {
            return [
                'student_name' => $sr->user->name ?? '-',
                'class'        => $sr->my_class->name ?? '-',
                'adm_no'       => $sr->adm_no ?? '-',
                'parent_name'  => $sr->my_parent->name ?? '-',
                'parent_role'  => ucfirst(str_replace('_', ' ', $sr->my_parent->user_type ?? '')),
            ];
        })->values();

        return [
            'sibling_families'   => $siblingFamilies,
            'employee_children'  => $employeeChildren,
            'sibling_count'      => $siblingStudents->count(),
            'employee_count'     => $employeeStudents->count(),
        ];
    }

    /** @deprecated Use getEligibleStudentsSummary() */
    public static function getEligibleStudents(): array
    {
        return self::getEligibleStudentsSummary();
    }

    /**
     * Apply automatic sibling / employee-child discount to a single invoice (no request needed).
     */
    public static function applyAutomaticDiscountToInvoice(StudentFeeInvoice $invoice): bool
    {
        $sr = StudentRecord::where('user_id', $invoice->student_id)->where('grad', 0)->first();
        if (!$sr) {
            return false;
        }

        $type = self::getDiscountTypeForStudent($sr);
        if (!$type) {
            return false;
        }

        $discount = self::calculateDiscountAmount((float) $invoice->original_amount, $type);
        $reason   = self::discountReasonLabel($type);

        $invoice->discount        = $discount;
        $invoice->discount_reason = $reason;
        $invoice->net_amount      = max(0, $invoice->original_amount - $discount + ($invoice->fine ?? 0));
        $invoice->save();
        $invoice->syncStatus();

        return true;
    }

    public static function discountReasonLabel(string $type): string
    {
        return $type === 'employee_child'
            ? 'Employee child discount (' . self::getEmployeePct() . '%)'
            : 'Sibling discount (' . self::getSiblingPct() . '%)';
    }

    public static function discountTypeLabel(string $type): string
    {
        return $type === 'employee_child' ? 'Employee Child' : 'Sibling';
    }

    /**
     * Family discount information shown to parents on the portal.
     */
    public static function getFamilyInfoForParent(int $parentId): array
    {
        $children = StudentRecord::with(['user', 'my_class'])
            ->where('my_parent_id', $parentId)
            ->where('grad', 0)
            ->get();

        $siblingEligible  = $children->count() >= 2;
        $employeeEligible = $children->contains(fn ($sr) => self::isEmployeeChildEligible($sr));

        return [
            'children'          => $children,
            'children_count'    => $children->count(),
            'sibling_eligible'  => $siblingEligible,
            'employee_eligible' => $employeeEligible,
            'sibling_pct'       => self::getSiblingPct(),
            'employee_pct'      => self::getEmployeePct(),
            'active_rule'       => $employeeEligible
                ? 'employee_child'
                : ($siblingEligible ? 'sibling' : null),
        ];
    }

    /**
     * Recalculate open invoices that qualify for automatic rule-based discounts.
     */
    public static function recalculateAllOpenInvoices(): int
    {
        $updated = 0;

        StudentFeeInvoice::whereIn('status', ['unpaid', 'partial'])
            ->chunkById(100, function ($invoices) use (&$updated) {
                foreach ($invoices as $invoice) {
                    if (self::applyAutomaticDiscountToInvoice($invoice)) {
                        $updated++;
                    }
                }
            });

        return $updated;
    }
}
