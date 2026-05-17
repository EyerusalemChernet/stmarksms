<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rebrand: replace all CJ Inspired / CJIA references in the settings table
 * with St. Mark School values. Safe to run multiple times (idempotent).
 */
class RebrandSettingsToStMarkSchool extends Migration
{
    public function up(): void
    {
        $updates = [
            'system_title'    => 'SMS',
            'system_name'     => 'St. Mark School',
            'system_email'    => 'info@stmarksms.com',
            'address'         => 'Addis Ababa, Ethiopia',
            'current_session' => '2024-2025',
        ];

        foreach ($updates as $type => $value) {
            $exists = DB::table('settings')->where('type', $type)->exists();
            if ($exists) {
                DB::table('settings')->where('type', $type)->update(['description' => $value]);
            } else {
                DB::table('settings')->insert(['type' => $type, 'description' => $value]);
            }
        }
    }

    public function down(): void
    {
        // No rollback — we don't want to restore CJ branding
    }
}
