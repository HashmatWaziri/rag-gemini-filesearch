<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| GLC placement AI model catalog
|--------------------------------------------------------------------------
|
| Admin-selectable providers and models for the two AI-assisted placement
| tasks: Writing evaluation (text LLM) and Speaking transcription (STT).
| Pricing was researched from official vendor pricing pages on 2026-06-11
| (see .firecrawl/ai-pricing). Prices are USD. Token prices are per 1M
| tokens; transcription prices are per audio minute unless noted.
|
| Provider keys reference entries in config/ai.php (Laravel AI SDK).
| Kimi (Moonshot) and MiniMax run through their OpenAI-compatible
| endpoints; Groq STT runs through its OpenAI-compatible audio endpoint.
*/

return [

    'pricing_retrieved_at' => '2026-06-11',

    'writing_evaluation' => [

        'default' => [
            'provider' => 'gemini',
            'model' => env('GLC_DRAFT_MODEL', 'gemini-2.5-flash'),
        ],

        'providers' => [
            'gemini' => [
                'label' => 'Google Gemini',
                'pricing_url' => 'https://ai.google.dev/gemini-api/docs/pricing',
                'models' => [
                    'gemini-3.1-pro-preview' => ['label' => 'Gemini 3.1 Pro Preview', 'input_per_mtok' => 2.00, 'output_per_mtok' => 12.00, 'tier' => 'flagship', 'notes' => 'Top Gemini reasoning quality; preview model. Prices for prompts <= 200k tokens.'],
                    'gemini-3.5-flash' => ['label' => 'Gemini 3.5 Flash', 'input_per_mtok' => 1.50, 'output_per_mtok' => 9.00, 'tier' => 'balanced', 'notes' => 'Latest Flash generation; strong quality with thinking tokens billed as output.'],
                    'gemini-2.5-pro' => ['label' => 'Gemini 2.5 Pro', 'input_per_mtok' => 1.25, 'output_per_mtok' => 10.00, 'tier' => 'balanced', 'notes' => 'Proven Pro model. Prices for prompts <= 200k tokens.'],
                    'gemini-2.5-flash' => ['label' => 'Gemini 2.5 Flash', 'input_per_mtok' => 0.30, 'output_per_mtok' => 2.50, 'tier' => 'value', 'notes' => 'Current platform default; reliable structured output at low cost.'],
                    'gemini-2.5-flash-lite' => ['label' => 'Gemini 2.5 Flash-Lite', 'input_per_mtok' => 0.10, 'output_per_mtok' => 0.40, 'tier' => 'budget', 'notes' => 'Cheapest Gemini option; adequate for short rubric scoring.'],
                ],
            ],
            'openai' => [
                'label' => 'OpenAI',
                'pricing_url' => 'https://openai.com/api/pricing/',
                'models' => [
                    'gpt-5.5' => ['label' => 'GPT-5.5', 'input_per_mtok' => 5.00, 'output_per_mtok' => 30.00, 'tier' => 'flagship', 'notes' => 'OpenAI flagship for professional work; highest quality and cost.'],
                    'gpt-5.4' => ['label' => 'GPT-5.4', 'input_per_mtok' => 2.50, 'output_per_mtok' => 15.00, 'tier' => 'balanced', 'notes' => 'Strong general model at half the flagship price.'],
                    'gpt-5.4-mini' => ['label' => 'GPT-5.4 mini', 'input_per_mtok' => 0.75, 'output_per_mtok' => 4.50, 'tier' => 'value', 'notes' => 'Best OpenAI price/quality ratio for rubric-style evaluation.'],
                ],
            ],
            'anthropic' => [
                'label' => 'Anthropic Claude',
                'pricing_url' => 'https://docs.anthropic.com/en/docs/about-claude/pricing',
                'models' => [
                    'claude-fable-5' => ['label' => 'Claude Fable 5', 'input_per_mtok' => 10.00, 'output_per_mtok' => 50.00, 'tier' => 'flagship', 'notes' => 'Newest Claude flagship; premium pricing.'],
                    'claude-opus-4-8' => ['label' => 'Claude Opus 4.8', 'input_per_mtok' => 5.00, 'output_per_mtok' => 25.00, 'tier' => 'flagship', 'notes' => 'Excellent nuanced language judgement for essay assessment.'],
                    'claude-sonnet-4-6' => ['label' => 'Claude Sonnet 4.6', 'input_per_mtok' => 3.00, 'output_per_mtok' => 15.00, 'tier' => 'balanced', 'notes' => 'Strong writing-quality evaluation; popular production default.'],
                    'claude-haiku-4-5' => ['label' => 'Claude Haiku 4.5', 'input_per_mtok' => 1.00, 'output_per_mtok' => 5.00, 'tier' => 'value', 'notes' => 'Fast and inexpensive with solid instruction following.'],
                ],
            ],
            'kimi' => [
                'label' => 'Kimi (Moonshot AI)',
                'pricing_url' => 'https://platform.kimi.ai/docs/pricing/chat-k26',
                'models' => [
                    'kimi-k2.6' => ['label' => 'Kimi K2.6', 'input_per_mtok' => 0.95, 'output_per_mtok' => 4.00, 'tier' => 'value', 'notes' => 'Open-weight frontier model via OpenAI-compatible endpoint; strong instruction compliance, 262k context. Input price is cache-miss ($0.16 on cache hit).'],
                ],
            ],
            'minimax' => [
                'label' => 'MiniMax',
                'pricing_url' => 'https://platform.minimax.io/docs/guides/pricing-paygo',
                'models' => [
                    'MiniMax-M3' => ['label' => 'MiniMax M3', 'input_per_mtok' => 0.30, 'output_per_mtok' => 1.20, 'tier' => 'value', 'notes' => 'Latest MiniMax model via OpenAI-compatible endpoint; promotional 50% discount applied (<= 512k input tokens).'],
                    'MiniMax-M2.7' => ['label' => 'MiniMax M2.7', 'input_per_mtok' => 0.30, 'output_per_mtok' => 1.20, 'tier' => 'budget', 'notes' => 'Cost-efficient agentic model; benchmarks competitively with Kimi K2 at lower cost.'],
                ],
            ],
            'deepseek' => [
                'label' => 'DeepSeek',
                'pricing_url' => 'https://api-docs.deepseek.com/quick_start/pricing',
                'models' => [
                    'deepseek-v4-flash' => ['label' => 'DeepSeek V4 Flash', 'input_per_mtok' => 0.14, 'output_per_mtok' => 0.28, 'tier' => 'budget', 'notes' => 'Exceptional price/performance; 1M context, thinking mode by default.'],
                    'deepseek-v4-pro' => ['label' => 'DeepSeek V4 Pro', 'input_per_mtok' => 0.435, 'output_per_mtok' => 0.87, 'tier' => 'value', 'notes' => 'Stronger DeepSeek tier, still far below Western flagship pricing.'],
                ],
            ],
        ],
    ],

    'speaking_evaluation' => [

        'default' => [
            'provider' => 'gemini',
            'model' => env('GLC_SPEAKING_EVAL_MODEL', 'gemini-2.5-flash'),
        ],

        'providers' => [
            'gemini' => [
                'label' => 'Google Gemini',
                'pricing_url' => 'https://ai.google.dev/gemini-api/docs/pricing',
                'models' => [
                    'gemini-3.1-pro-preview' => ['label' => 'Gemini 3.1 Pro Preview', 'input_per_mtok' => 2.00, 'output_per_mtok' => 12.00, 'tier' => 'flagship', 'notes' => 'Top Gemini reasoning quality; preview model. Prices for prompts <= 200k tokens.'],
                    'gemini-3.5-flash' => ['label' => 'Gemini 3.5 Flash', 'input_per_mtok' => 1.50, 'output_per_mtok' => 9.00, 'tier' => 'balanced', 'notes' => 'Latest Flash generation; strong quality with thinking tokens billed as output.'],
                    'gemini-2.5-flash' => ['label' => 'Gemini 2.5 Flash', 'input_per_mtok' => 0.30, 'output_per_mtok' => 2.50, 'tier' => 'value', 'notes' => 'Current platform default; reliable structured output at low cost.'],
                    'gemini-2.5-flash-lite' => ['label' => 'Gemini 2.5 Flash-Lite', 'input_per_mtok' => 0.10, 'output_per_mtok' => 0.40, 'tier' => 'budget', 'notes' => 'Cheapest Gemini option; adequate for short rubric scoring.'],
                ],
            ],
            'openai' => [
                'label' => 'OpenAI',
                'pricing_url' => 'https://openai.com/api/pricing/',
                'models' => [
                    'gpt-5.5' => ['label' => 'GPT-5.5', 'input_per_mtok' => 5.00, 'output_per_mtok' => 30.00, 'tier' => 'flagship', 'notes' => 'OpenAI flagship for professional work; highest quality and cost.'],
                    'gpt-5.4' => ['label' => 'GPT-5.4', 'input_per_mtok' => 2.50, 'output_per_mtok' => 15.00, 'tier' => 'balanced', 'notes' => 'Strong general model at half the flagship price.'],
                    'gpt-5.4-mini' => ['label' => 'GPT-5.4 mini', 'input_per_mtok' => 0.75, 'output_per_mtok' => 4.50, 'tier' => 'value', 'notes' => 'Best OpenAI price/quality ratio for rubric-style evaluation.'],
                ],
            ],
            'anthropic' => [
                'label' => 'Anthropic Claude',
                'pricing_url' => 'https://docs.anthropic.com/en/docs/about-claude/pricing',
                'models' => [
                    'claude-sonnet-4-6' => ['label' => 'Claude Sonnet 4.6', 'input_per_mtok' => 3.00, 'output_per_mtok' => 15.00, 'tier' => 'balanced', 'notes' => 'Strong language-quality evaluation; popular production default.'],
                    'claude-haiku-4-5' => ['label' => 'Claude Haiku 4.5', 'input_per_mtok' => 1.00, 'output_per_mtok' => 5.00, 'tier' => 'value', 'notes' => 'Fast and inexpensive with solid instruction following.'],
                ],
            ],
            'deepseek' => [
                'label' => 'DeepSeek',
                'pricing_url' => 'https://api-docs.deepseek.com/quick_start/pricing',
                'models' => [
                    'deepseek-v4-flash' => ['label' => 'DeepSeek V4 Flash', 'input_per_mtok' => 0.14, 'output_per_mtok' => 0.28, 'tier' => 'budget', 'notes' => 'Exceptional price/performance; 1M context, thinking mode by default.'],
                    'deepseek-v4-pro' => ['label' => 'DeepSeek V4 Pro', 'input_per_mtok' => 0.435, 'output_per_mtok' => 0.87, 'tier' => 'value', 'notes' => 'Stronger DeepSeek tier, still far below Western flagship pricing.'],
                ],
            ],
        ],
    ],

    'speaking_transcription' => [

        'default' => [
            'provider' => 'gemini',
            'model' => 'gemini-2.5-flash',
        ],

        'providers' => [
            'gemini' => [
                'label' => 'Google Gemini',
                'pricing_url' => 'https://ai.google.dev/gemini-api/docs/pricing',
                'models' => [
                    'gemini-2.5-flash' => ['label' => 'Gemini 2.5 Flash (audio)', 'per_minute' => 0.0019, 'tier' => 'value', 'notes' => 'Native audio understanding ($1.00/M audio tokens at ~32 tokens/sec); transcribes and can reason about delivery.'],
                    'gemini-2.5-flash-lite' => ['label' => 'Gemini 2.5 Flash-Lite (audio)', 'per_minute' => 0.0006, 'tier' => 'budget', 'notes' => 'Cheapest Gemini audio option ($0.30/M audio tokens).'],
                ],
            ],
            'openai' => [
                'label' => 'OpenAI',
                'pricing_url' => 'https://platform.openai.com/docs/pricing',
                'models' => [
                    'gpt-4o-transcribe' => ['label' => 'GPT-4o Transcribe', 'per_minute' => 0.006, 'tier' => 'balanced', 'notes' => 'Better-than-Whisper accuracy on accented speech; ideal for ESL candidates.'],
                    'gpt-4o-mini-transcribe' => ['label' => 'GPT-4o mini Transcribe', 'per_minute' => 0.003, 'tier' => 'value', 'notes' => 'Half the price of GPT-4o Transcribe with strong accuracy.'],
                    'whisper-1' => ['label' => 'Whisper v2 (whisper-1)', 'per_minute' => 0.006, 'tier' => 'balanced', 'notes' => 'Battle-tested baseline; ~7.4% average WER on FLEURS.'],
                ],
            ],
            'eleven' => [
                'label' => 'ElevenLabs',
                'pricing_url' => 'https://elevenlabs.io/pricing/api',
                'models' => [
                    'scribe_v1' => ['label' => 'Scribe v1/v2', 'per_minute' => 0.0037, 'tier' => 'balanced', 'notes' => 'Claims over 98% transcription accuracy, 90+ languages ($0.22/hour).'],
                ],
            ],
            'mistral' => [
                'label' => 'Mistral (Voxtral)',
                'pricing_url' => 'https://mistral.ai/pricing',
                'models' => [
                    'voxtral-mini-latest' => ['label' => 'Voxtral Mini Transcribe 2', 'per_minute' => 0.003, 'tier' => 'value', 'notes' => 'State-of-the-art transcription (~5.9% avg WER on FLEURS, beats Whisper).'],
                ],
            ],
            'groq-stt' => [
                'label' => 'Groq (Whisper)',
                'pricing_url' => 'https://groq.com/pricing',
                'models' => [
                    'whisper-large-v3' => ['label' => 'Whisper Large v3 on Groq', 'per_minute' => 0.00185, 'tier' => 'budget', 'notes' => 'Open Whisper weights on Groq LPUs ($0.111/hour); very fast batch transcription.'],
                    'whisper-large-v3-turbo' => ['label' => 'Whisper Large v3 Turbo on Groq', 'per_minute' => 0.00067, 'tier' => 'budget', 'notes' => 'Cheapest option in the catalog ($0.04/hour); slightly lower accuracy than full v3.'],
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Writing evaluation guidelines (defaults)
    |--------------------------------------------------------------------------
    |
    | Default rubric criteria the AI evaluates Writing submissions against.
    | Adapted from the public IELTS Writing band descriptors (Task
    | achievement, Coherence and cohesion, Lexical resource, Grammatical
    | range and accuracy). Admins and supervisors manage the effective list
    | via the UI (stored in settings under SettingKey::GlcWritingGuidelines);
    | these defaults apply until then.
    */
    'writing_guidelines' => [
        'defaults' => [
            ['title' => 'Task achievement', 'description' => 'The response addresses all parts of the prompt with a clear position throughout, presents relevant main ideas that are extended and supported with examples, and stays within the expected word count (150–250 words).'],
            ['title' => 'Coherence and cohesion', 'description' => 'Ideas are organised logically with clear progression across the text. Paragraphing is appropriate, and cohesive devices (linking words, referencing, substitution) connect ideas naturally without being mechanical or overused.'],
            ['title' => 'Lexical resource', 'description' => 'Range and precision of vocabulary: the candidate goes beyond basic words, uses collocation and some less common items appropriately, and spelling or word-formation errors do not impede communication.'],
            ['title' => 'Grammatical range and accuracy', 'description' => 'A mix of simple and complex sentence structures used flexibly and accurately. Errors in grammar and punctuation are rare or minor and do not reduce clarity for the reader.'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Speaking evaluation guidelines (defaults)
    |--------------------------------------------------------------------------
    |
    | Default rubric criteria the AI evaluates Speaking transcripts against.
    | Adapted from the public IELTS Speaking band descriptors, with
    | pronunciation replaced by transcript-based comprehensibility because
    | the AI only sees the transcript — GLC staff judge pronunciation from
    | the recording during review. Admins and supervisors manage the
    | effective list via the UI (stored in settings under
    | SettingKey::GlcSpeakingGuidelines).
    */
    'speaking_guidelines' => [
        'defaults' => [
            ['title' => 'Fluency and coherence', 'description' => 'The response flows at a natural length with connected, logically ordered ideas. Hesitation visible in the transcript (fragments, abandoned sentences, fillers) is limited, and the candidate develops topics without losing the thread.'],
            ['title' => 'Lexical resource', 'description' => 'Vocabulary range and flexibility: the candidate paraphrases when needed, uses topic-appropriate and some less common vocabulary, and word choice rarely obscures meaning.'],
            ['title' => 'Grammatical range and accuracy', 'description' => 'A mix of simple and complex spoken structures with consistent control. Errors occur naturally in spontaneous speech but rarely cause misunderstanding.'],
            ['title' => 'Task fulfilment', 'description' => 'The response addresses every part of the speaking prompt with sufficient development within the recording time, staying on topic throughout.'],
            ['title' => 'Comprehensibility', 'description' => 'How easily a listener can follow the response based on the transcript: clear phrasing, smooth self-correction, and meaning rarely obscured by errors. Pronunciation is judged by GLC staff during review, not by the AI.'],
        ],
    ],

    'tutor_chat' => [

        'default' => [
            'provider' => 'gemini',
            'model' => env('GLC_TUTOR_MODEL', 'gemini-2.5-flash'),
        ],

        'providers' => [
            'gemini' => [
                'label' => 'Google Gemini',
                'pricing_url' => 'https://ai.google.dev/gemini-api/docs/pricing',
                'models' => [
                    'gemini-3.5-flash' => ['label' => 'Gemini 3.5 Flash', 'input_per_mtok' => 1.50, 'output_per_mtok' => 9.00, 'tier' => 'balanced', 'notes' => 'Latest Flash generation with File Search support.'],
                    'gemini-2.5-pro' => ['label' => 'Gemini 2.5 Pro', 'input_per_mtok' => 1.25, 'output_per_mtok' => 10.00, 'tier' => 'balanced', 'notes' => 'Stronger reasoning for complex tutoring exchanges.'],
                    'gemini-2.5-flash' => ['label' => 'Gemini 2.5 Flash', 'input_per_mtok' => 0.30, 'output_per_mtok' => 2.50, 'tier' => 'value', 'notes' => 'Current tutor default; fast curriculum-grounded replies.'],
                    'gemini-2.5-flash-lite' => ['label' => 'Gemini 2.5 Flash-Lite', 'input_per_mtok' => 0.10, 'output_per_mtok' => 0.40, 'tier' => 'budget', 'notes' => 'Cheapest option for high-volume tutoring.'],
                ],
            ],
        ],
    ],

    'tutor_writing' => [

        'default' => [
            'provider' => 'gemini',
            'model' => env('GLC_TUTOR_MODEL', 'gemini-2.5-flash'),
        ],

        'providers' => [
            'gemini' => [
                'label' => 'Google Gemini',
                'pricing_url' => 'https://ai.google.dev/gemini-api/docs/pricing',
                'models' => [
                    'gemini-2.5-flash' => ['label' => 'Gemini 2.5 Flash', 'input_per_mtok' => 0.30, 'output_per_mtok' => 2.50, 'tier' => 'value', 'notes' => 'Current default for tutor writing correction.'],
                    'gemini-2.5-flash-lite' => ['label' => 'Gemini 2.5 Flash-Lite', 'input_per_mtok' => 0.10, 'output_per_mtok' => 0.40, 'tier' => 'budget', 'notes' => 'Lower cost for short writing submissions.'],
                ],
            ],
        ],
    ],

    'tutor_progress' => [

        'default' => [
            'provider' => 'gemini',
            'model' => env('GLC_TUTOR_MODEL', 'gemini-2.5-flash'),
        ],

        'providers' => [
            'gemini' => [
                'label' => 'Google Gemini',
                'pricing_url' => 'https://ai.google.dev/gemini-api/docs/pricing',
                'models' => [
                    'gemini-2.5-flash' => ['label' => 'Gemini 2.5 Flash', 'input_per_mtok' => 0.30, 'output_per_mtok' => 2.50, 'tier' => 'value', 'notes' => 'Staff-only tutor progress summaries.'],
                    'gemini-2.5-flash-lite' => ['label' => 'Gemini 2.5 Flash-Lite', 'input_per_mtok' => 0.10, 'output_per_mtok' => 0.40, 'tier' => 'budget', 'notes' => 'Lower cost for periodic progress reports.'],
                ],
            ],
        ],
    ],

    /*
    | Maps catalog provider names to the credential they share. Providers
    | not listed here use their own name as the credential key.
    */
    'credential_aliases' => [
        'groq-stt' => 'groq',
    ],
];
