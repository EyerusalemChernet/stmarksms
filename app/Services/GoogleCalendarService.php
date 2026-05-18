<?php

namespace App\Services;

use App\Models\CalendarEvent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Google Calendar integration via Service Account (no user OAuth needed).
 *
 * Setup steps:
 *  1. Go to https://console.cloud.google.com
 *  2. Create a project → Enable "Google Calendar API"
 *  3. Create a Service Account → download JSON key
 *  4. Save the JSON file to storage/app/google-service-account.json
 *  5. Share your Google Calendar with the service account email (give it "Make changes to events")
 *  6. Set GOOGLE_CALENDAR_ID in .env to your calendar's ID
 */
class GoogleCalendarService
{
    protected string $calendarId;
    protected ?string $accessToken = null;

    public function __construct()
    {
        $this->calendarId = env('GOOGLE_CALENDAR_ID', 'primary');
    }

    // ── Public API ────────────────────────────────────────────────────────────

    public function pushEvent(CalendarEvent $event): ?string
    {
        $token = $this->getAccessToken();
        if (!$token) return null;

        $body = $this->buildEventBody($event);

        try {
            $response = Http::withToken($token)
                ->post("https://www.googleapis.com/calendar/v3/calendars/{$this->calendarId}/events", $body);

            if ($response->successful()) {
                return $response->json('id');
            }
            Log::warning('Google Calendar push failed: '.$response->body());
        } catch (\Exception $e) {
            Log::warning('Google Calendar push exception: '.$e->getMessage());
        }
        return null;
    }

    public function updateEvent(CalendarEvent $event): bool
    {
        if (!$event->google_event_id) return false;
        $token = $this->getAccessToken();
        if (!$token) return false;

        $body = $this->buildEventBody($event);

        try {
            $response = Http::withToken($token)
                ->put("https://www.googleapis.com/calendar/v3/calendars/{$this->calendarId}/events/{$event->google_event_id}", $body);
            return $response->successful();
        } catch (\Exception $e) {
            Log::warning('Google Calendar update exception: '.$e->getMessage());
            return false;
        }
    }

    public function deleteEvent(string $googleEventId): bool
    {
        $token = $this->getAccessToken();
        if (!$token) return false;

        try {
            $response = Http::withToken($token)
                ->delete("https://www.googleapis.com/calendar/v3/calendars/{$this->calendarId}/events/{$googleEventId}");
            return $response->successful() || $response->status() === 410; // 410 = already deleted
        } catch (\Exception $e) {
            Log::warning('Google Calendar delete exception: '.$e->getMessage());
            return false;
        }
    }

    /**
     * Generate a public "Add to Google Calendar" URL for a single event.
     * No API key needed — works for any user clicking the link.
     */
    public static function addToGoogleUrl(CalendarEvent $event): string
    {
        $start = $event->start_date->format('Ymd');
        $end   = $event->end_date
            ? $event->end_date->addDay()->format('Ymd')   // Google uses exclusive end date
            : $event->start_date->addDay()->format('Ymd');

        if (!$event->all_day && $event->start_time) {
            $start = $event->start_date->format('Ymd').'T'.str_replace(':', '', substr($event->start_time, 0, 5)).'00';
            $end   = $event->end_date
                ? $event->end_date->format('Ymd').'T'.str_replace(':', '', substr($event->end_time ?? $event->start_time, 0, 5)).'00'
                : $start;
        }

        return 'https://calendar.google.com/calendar/render?action=TEMPLATE'
            .'&text='.urlencode($event->title)
            .'&dates='.$start.'/'.$end
            .($event->description ? '&details='.urlencode($event->description) : '')
            .'&sf=true&output=xml';
    }

    /**
     * Generate the ICS subscribe URL for this app's calendar feed.
     */
    public static function icsSubscribeUrl(): string
    {
        // Google Calendar needs a publicly accessible URL (not localhost)
        return url('/calendar/ics');
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    protected function buildEventBody(CalendarEvent $event): array
    {
        $tz = config('app.timezone', 'Africa/Addis_Ababa');

        if ($event->all_day) {
            $start = ['date' => $event->start_date->format('Y-m-d')];
            $end   = ['date' => ($event->end_date ?? $event->start_date)->addDay()->format('Y-m-d')];
        } else {
            $startDt = $event->start_date->format('Y-m-d').'T'.($event->start_time ?? '00:00:00');
            $endDt   = ($event->end_date ?? $event->start_date)->format('Y-m-d').'T'.($event->end_time ?? $event->start_time ?? '01:00:00');
            $start   = ['dateTime' => $startDt, 'timeZone' => $tz];
            $end     = ['dateTime' => $endDt,   'timeZone' => $tz];
        }

        return [
            'summary'     => $event->title,
            'description' => $event->description ?? '',
            'start'       => $start,
            'end'         => $end,
            'colorId'     => $this->colorToGoogleId($event->color),
        ];
    }

    protected function colorToGoogleId(string $hex): string
    {
        // Google Calendar color IDs 1-11
        return match($hex) {
            '#ef4444' => '11', // Tomato
            '#f59e0b' => '5',  // Banana
            '#10b981' => '2',  // Sage
            '#3b82f6' => '9',  // Blueberry
            '#8b5cf6' => '3',  // Grape
            '#ec4899' => '4',  // Flamingo
            default   => '9',  // Blueberry (default)
        };
    }

    /**
     * Get a short-lived access token using the service account JWT flow.
     * No external packages needed — pure HTTP.
     */
    protected function getAccessToken(): ?string
    {
        if ($this->accessToken) return $this->accessToken;

        $jsonPath = env('GOOGLE_SERVICE_ACCOUNT_JSON', 'google-service-account.json');

        if (!Storage::exists($jsonPath)) {
            Log::info('Google service account JSON not found at storage/app/'.$jsonPath.'. Google Calendar sync skipped.');
            return null;
        }

        $sa = json_decode(Storage::get($jsonPath), true);
        if (!$sa || empty($sa['private_key'])) {
            Log::warning('Invalid Google service account JSON.');
            return null;
        }

        try {
            $now    = time();
            $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $claim  = base64_encode(json_encode([
                'iss'   => $sa['client_email'],
                'scope' => 'https://www.googleapis.com/auth/calendar',
                'aud'   => 'https://oauth2.googleapis.com/token',
                'iat'   => $now,
                'exp'   => $now + 3600,
            ]));

            $sigInput = $header.'.'.$claim;
            openssl_sign($sigInput, $sig, $sa['private_key'], 'SHA256');
            $jwt = $sigInput.'.'.base64_encode($sig);

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]);

            if ($response->successful()) {
                $this->accessToken = $response->json('access_token');
                return $this->accessToken;
            }

            Log::warning('Google token exchange failed: '.$response->body());
        } catch (\Exception $e) {
            Log::warning('Google JWT signing failed: '.$e->getMessage());
        }

        return null;
    }
}
