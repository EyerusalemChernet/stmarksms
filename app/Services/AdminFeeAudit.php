<?php

namespace App\Services;

use App\Models\StudentFeeInvoice;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class AdminFeeAudit
{
    public static function isSuperAdmin(): bool
    {
        return Auth::check() && Auth::user()->user_type === 'super_admin';
    }

    /** Super Admin has created or edited this category/structure (accountant cannot edit). */
    public static function isLockedForAccountant(?Model $record): bool
    {
        return $record && !empty($record->admin_updated_at);
    }

    public static function now(): Carbon
    {
        return Carbon::now(config('app.timezone'));
    }

    public static function formatAt($at): string
    {
        if (!$at) {
            return '—';
        }

        return Carbon::parse($at)->timezone(config('app.timezone'))->format('d M Y, H:i:s');
    }

    /** Record and persist Super Admin create/update on fee category or structure. */
    public static function stamp(Model $model, string $action, ?string $note = null): void
    {
        if (!self::isSuperAdmin() || !$model->getKey()) {
            return;
        }

        $at = self::now();
        $data = [
            'admin_updated_by'   => Auth::id(),
            'admin_updated_at'   => $at->format('Y-m-d H:i:s'),
            'admin_action'       => $action,
            'admin_update_note'  => $note ?: ($action === 'created' ? 'Created by Super Admin' : 'Updated by Super Admin'),
        ];

        $model->getConnection()->table($model->getTable())
            ->where($model->getKeyName(), $model->getKey())
            ->update($data);

        $model->forceFill($data);
        $model->syncOriginal();
    }

    /** Record and persist Super Admin edit on an invoice. */
    public static function stampInvoice(StudentFeeInvoice $invoice, string $note): void
    {
        if (!self::isSuperAdmin()) {
            return;
        }

        $at = self::now();
        $data = [
            'updated_by'         => Auth::id(),
            'admin_updated_at'   => $at->format('Y-m-d H:i:s'),
            'admin_update_note'  => $note,
        ];

        $invoice->getConnection()->table($invoice->getTable())
            ->where('id', $invoice->id)
            ->update($data);

        $invoice->forceFill($data);
        $invoice->syncOriginal();
    }

    /**
     * @return Collection<int, array{label: string, action: string, user: string, at: Carbon, note: ?string}>
     */
    public static function eventsForInvoice(StudentFeeInvoice $inv): Collection
    {
        $events = collect();
        $structure = $inv->fee_structure;
        $category  = $structure?->category;

        foreach ([
            ['label' => 'Fee category', 'model' => $category],
            ['label' => 'Fee structure', 'model' => $structure],
            ['label' => 'Invoice', 'model' => $inv],
        ] as $item) {
            $model = $item['model'];
            if (!$model) {
                continue;
            }

            $at = $model->admin_updated_at ?? null;
            if (!$at) {
                continue;
            }

            $updater = $model->adminUpdater ?? null;
            if (!$updater) {
                continue;
            }

            $action = $item['label'] === 'Invoice'
                ? 'updated'
                : ($model->admin_action ?? 'updated');

            $events->push([
                'label'  => $item['label'],
                'action' => $action,
                'user'   => $updater->name,
                'at'     => Carbon::parse($at)->timezone(config('app.timezone')),
                'note'   => $model->admin_update_note ?? null,
            ]);
        }

        return $events->unique(fn ($e) => $e['label'] . '|' . $e['at']->timestamp . '|' . $e['action'])
            ->sortByDesc(fn ($e) => $e['at']->timestamp)
            ->values();
    }

    public static function latestEventForInvoice(StudentFeeInvoice $inv): ?array
    {
        return self::eventsForInvoice($inv)->first();
    }

    public static function hasEventsForInvoice(StudentFeeInvoice $inv): bool
    {
        return self::eventsForInvoice($inv)->isNotEmpty();
    }
}
