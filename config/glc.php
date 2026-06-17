<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| GLC AI Platform configuration (Phase 1)
|--------------------------------------------------------------------------
|
| Planning defaults from GLC_PRD_Phase1.md. Values marked "configurable"
| may be overridden by Admin via Settings (App\Enums\SettingKey) within
| these defined bounds. Placeholder values apply until GLC supplies finals.
*/

return [

    'placement' => [

        // Per-section time limits in seconds (PRD: Reading 15, G/V 12,
        // Listening 10, Writing 25, Speaking 8 minutes - configurable).
        'section_time_limits' => [
            'reading' => (int) env('GLC_TIME_LIMIT_READING', 900),
            'grammar_vocabulary' => (int) env('GLC_TIME_LIMIT_GRAMMAR', 720),
            'listening' => (int) env('GLC_TIME_LIMIT_LISTENING', 600),
            'writing' => (int) env('GLC_TIME_LIMIT_WRITING', 1500),
            'speaking' => (int) env('GLC_TIME_LIMIT_SPEAKING', 480),
        ],

        // Fixed-form content planning defaults.
        'reading_passages' => 2,
        'reading_questions_per_passage' => 6,
        'grammar_vocabulary_items' => 22,
        'listening_clips' => 2,
        'listening_questions_per_clip' => 5,

        'listening' => [
            // Countdown before the first unplayed clip starts automatically.
            'auto_start_seconds' => 10,
        ],

        'writing' => [
            'min_words' => 150,
            'max_words' => 250, // soft warning above this, submission still allowed
            'autosave_interval_seconds' => 5,
        ],

        'speaking' => [
            'max_attempts' => 3, // failed quality checks do not count
            'max_duration_seconds' => 180,
        ],

        // Pre-test audio setup wizard (speaker test, mic test, recorded
        // mic check transcribed by Gemini when a key is configured).
        'device_check' => [
            'recording_max_seconds' => 10,
            'recording_max_kilobytes' => 5120,
        ],

        // Session behavior.
        'resume_window_hours' => 24,
        'inactivity_pause_seconds' => 1800, // 30 minutes pauses section timer
        'minimum_age' => 12, // under-12 candidates are blocked

        // Scoring: equal 20% weight per section; seven GLC levels.
        'section_weights' => [
            'reading' => 0.20,
            'grammar_vocabulary' => 0.20,
            'listening' => 0.20,
            'writing' => 0.20,
            'speaking' => 0.20,
        ],

        // Minimum composite percentage for each GLC level (Starter is below Beginner).
        'level_band_minimums' => [
            'beginner' => 15.0,
            'elementary' => 30.0,
            'pre_intermediate' => 45.0,
            'intermediate' => 60.0,
            'upper_intermediate' => 75.0,
            'advanced' => 90.0,
        ],

        'variance_flag_threshold' => 30.0, // pct-point spread that flags supervisor review

        // Result delivery.
        'result_link_days' => 30,
    ],

    'guardian_consent' => [
        // Ages 12-17 require guardian info + Admin manual consent flag
        // before tutor access or placement result send.
        'min_age' => 12,
        'max_age' => 17,
    ],

    'curriculum' => [
        'allowed_extensions' => ['pdf', 'docx', 'txt'],
        'max_file_size_kb' => 20480,
        'max_bulk_files' => 20,
        'store_display_name' => 'GLC Curriculum Store',
    ],

    'tutor' => [
        'model' => env('GLC_TUTOR_MODEL', 'gemini-2.5-flash'),
        // Conversation rotation: summarize oldest 20 pairs past 40 pairs.
        'rotation_threshold_pairs' => 40,
        'rotation_summarize_pairs' => 20,
        // Persistent direct-answer seeking notification threshold.
        'violation_notification_threshold' => 3,
        'violation_notification_window_days' => 7,
        // Staff activity roster: violations within this window surface as "needs attention".
        'activity_attention_window_days' => 30,
        // Phase 2 analytics (usage-time rollups, weak areas, AI progress reports).
        'progress_analytics_enabled' => env('GLC_TUTOR_PROGRESS_ANALYTICS', false),
        'usage_active_gap_minutes' => 5,
        'usage_daily_active_minutes_cap' => 120,
    ],

    'ai_drafts' => [
        'model' => env('GLC_DRAFT_MODEL', 'gemini-2.5-flash'),
    ],

    'guardrail_uat' => [
        // Phase 1C gate: max 2 direct-answer failures out of 50 (5%).
        'question_count' => 50,
        'max_failures' => 2,
    ],
];
