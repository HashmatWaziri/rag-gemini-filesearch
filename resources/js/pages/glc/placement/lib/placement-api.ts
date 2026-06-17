/**
 * Fetch helpers for the placement candidate JSON endpoints (auto-save,
 * heartbeat, integrity, audio). Sends the XSRF token Laravel sets as a
 * cookie and follows server-directed redirects (terminated/expired/
 * submitted sessions).
 */

export interface ApiResult<T> {
    ok: boolean;
    status: number;
    data: T & { message?: string; redirect?: string };
}

function xsrfToken(): string {
    const match = document.cookie
        .split('; ')
        .find((row) => row.startsWith('XSRF-TOKEN='));

    return match ? decodeURIComponent(match.split('=')[1]) : '';
}

async function request<T>(
    url: string,
    body?: Record<string, unknown> | FormData,
): Promise<ApiResult<T>> {
    const isForm = body instanceof FormData;

    const response = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-XSRF-TOKEN': xsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            ...(isForm ? {} : { 'Content-Type': 'application/json' }),
        },
        body: isForm ? body : JSON.stringify(body ?? {}),
    });

    let data: ApiResult<T>['data'];
    try {
        data = (await response.json()) as ApiResult<T>['data'];
    } catch {
        data = {} as ApiResult<T>['data'];
    }

    // Session-state redirects (terminated, expired, already submitted).
    if (!response.ok && data.redirect) {
        window.location.href = data.redirect;
    }

    return { ok: response.ok, status: response.status, data };
}

export const placementApi = {
    validateCode: (code: string) =>
        request<{ valid?: boolean; resume?: boolean; redirect?: string }>(
            '/placement/validate-code',
            { code },
        ),

    saveAnswer: (itemId: number, answer: number | string) =>
        request<{ saved?: boolean; savedAt?: string }>(
            '/placement/answers',
            typeof answer === 'number'
                ? { item_id: itemId, selected: answer }
                : { item_id: itemId, text: answer },
        ),

    saveWriting: (text: string) =>
        request<{ saved?: boolean; wordCount?: number; savedAt?: string }>(
            '/placement/writing',
            { text },
        ),

    heartbeat: () =>
        request<{
            remainingSeconds?: number;
            sectionCompleted?: boolean;
            redirect?: string;
        }>('/placement/heartbeat'),

    registerPlay: (itemId: number) =>
        request<{ url?: string; played?: boolean }>(
            `/placement/listening/play/${itemId}`,
        ),

    uploadRecording: (form: FormData) =>
        request<{
            counted?: boolean;
            attemptsUsed?: number;
            attemptsRemaining?: number;
        }>('/placement/speaking', form),

    transcribeMicCheck: (form: FormData) =>
        request<{
            transcript?: string | null;
            transcriptionAvailable?: boolean;
        }>('/placement/device-check/transcribe', form),

    reportIntegrity: (type: 'tab_switch' | 'paste_attempt', context?: string) =>
        request<{ recorded?: boolean }>('/placement/integrity', {
            type,
            context,
        }),
};
