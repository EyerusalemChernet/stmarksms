<?php

namespace App\Services;

use App\Models\Section;
use Illuminate\Support\Collection;

class RedistributionService
{
    /**
     * Assign proposed_section_id to each draft using the selected mode.
     * Returns the modified drafts collection.
     */
    public function distribute(Collection $drafts, Collection $targetSections, string $mode): Collection
    {
        return match($mode) {
            'keep_same' => $this->keepSame($drafts, $targetSections),
            'balanced'  => $this->balanced($drafts, $targetSections),
            'manual'    => $this->manual($drafts),
            default     => $this->random($drafts, $targetSections),
        };
    }

    /**
     * keep_same: match section name in target class; fall back to first section.
     */
    private function keepSame(Collection $drafts, Collection $targetSections): Collection
    {
        $sectionsByName = $targetSections->keyBy(fn($s) => strtolower(trim($s->name)));
        $fallback = $targetSections->first();

        return $drafts->map(function ($draft) use ($sectionsByName, $fallback) {
            $currentName = strtolower(trim($draft->currentSection?->name ?? ''));
            $match = $sectionsByName[$currentName] ?? $fallback;
            $draft->proposed_section_id = $match?->id;
            return $draft;
        });
    }

    /**
     * random: shuffle + round-robin, max variance of 1 between sections.
     */
    private function random(Collection $drafts, Collection $targetSections): Collection
    {
        $shuffled = $drafts->shuffle()->values();
        $sections = $targetSections->values();
        $count    = $sections->count();

        if ($count === 0) return $drafts;

        return $shuffled->map(function ($draft, $i) use ($sections, $count) {
            $draft->proposed_section_id = $sections[$i % $count]->id;
            return $draft;
        });
    }

    /**
     * balanced: capacity → gender balance (±1) → score distribution → locked students.
     */
    private function balanced(Collection $drafts, Collection $targetSections): Collection
    {
        // Separate locked and unlocked
        $locked   = $drafts->where('is_locked', true)->values();
        $unlocked = $drafts->where('is_locked', false)->sortByDesc('yearly_average')->values();

        // Build section state
        $state = $targetSections->mapWithKeys(fn($s) => [
            $s->id => [
                'section'  => $s,
                'capacity' => $s->capacity ?? PHP_INT_MAX,
                'count'    => 0,
                'boys'     => 0,
                'girls'    => 0,
            ]
        ])->toArray();

        // Pre-fill locked students into state
        foreach ($locked as $draft) {
            if ($draft->proposed_section_id && isset($state[$draft->proposed_section_id])) {
                $sid = $draft->proposed_section_id;
                $state[$sid]['count']++;
                $gender = strtolower($draft->student?->gender ?? '');
                if ($gender === 'male')   $state[$sid]['boys']++;
                if ($gender === 'female') $state[$sid]['girls']++;
            }
        }

        // Assign unlocked students
        foreach ($unlocked as $draft) {
            // Find best section: not full, then most gender-balanced, then least full
            $best = null;
            $bestScore = PHP_INT_MAX;

            foreach ($state as $sid => $s) {
                if ($s['count'] >= $s['capacity']) continue;
                $genderImbalance = abs($s['boys'] - $s['girls']);
                $score = $genderImbalance * 100 + $s['count'];
                if ($score < $bestScore) {
                    $bestScore = $score;
                    $best = $sid;
                }
            }

            // If all sections full, pick least full
            if ($best === null) {
                $best = collect($state)->sortBy('count')->keys()->first();
            }

            $draft->proposed_section_id = $best;
            $state[$best]['count']++;
            $gender = strtolower($draft->student?->gender ?? '');
            if ($gender === 'male')   $state[$best]['boys']++;
            if ($gender === 'female') $state[$best]['girls']++;
        }

        return $drafts;
    }

    /**
     * manual: set all proposed_section_id = null.
     */
    private function manual(Collection $drafts): Collection
    {
        return $drafts->map(function ($draft) {
            $draft->proposed_section_id = null;
            $draft->is_locked = false;
            return $draft;
        });
    }

    /**
     * Rebalance only unlocked students, preserve locked assignments.
     */
    public function rebalanceUnlocked(Collection $drafts, Collection $targetSections): Collection
    {
        $locked   = $drafts->where('is_locked', true);
        $unlocked = $drafts->where('is_locked', false)->values();
        $rebalanced = $this->balanced($unlocked, $targetSections);

        return $locked->merge($rebalanced);
    }
}
