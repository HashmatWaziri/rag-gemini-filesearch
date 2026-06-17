import { BookOpen, CheckCircle2, UserRound } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

export type SetupStepKey = 'materials' | 'assign' | 'ready';

export type SetupStepState = 'done' | 'current' | 'upcoming';

export interface SetupStep {
    key: SetupStepKey;
    title: string;
    shortTitle: string;
    role: string;
    state: SetupStepState;
    note?: string;
}

export interface SetupPipelineInput {
    scopeSelected: boolean;
    materialsReady: boolean;
    assignmentMatchesScope: boolean;
    setupComplete: boolean;
    canManageCurriculum: boolean;
}

const STEP_META: Record<
    SetupStepKey,
    { title: string; shortTitle: string; role: string; Icon: LucideIcon }
> = {
    materials: {
        title: 'Confirm materials are live',
        shortTitle: 'Materials readiness',
        role: 'Academic Supervisor or Admin',
        Icon: BookOpen,
    },
    assign: {
        title: 'Assign student to unit',
        shortTitle: 'Assign student',
        role: 'Teacher, Academic Supervisor or Admin',
        Icon: UserRound,
    },
    ready: {
        title: 'Tutor ready for student',
        shortTitle: 'Ready',
        role: 'Student can start chatting',
        Icon: CheckCircle2,
    },
};

export function deriveSetupSteps(input: SetupPipelineInput): SetupStep[] {
    const materialsDone = input.materialsReady;
    const assignDone = input.assignmentMatchesScope;
    const allDone = input.setupComplete;

    const raw = [
        {
            key: 'materials' as const,
            done: materialsDone,
            note: !input.scopeSelected
                ? 'Choose a course, level, and unit below'
                : materialsDone
                  ? 'At least one document is live for the AI Tutor'
                  : input.canManageCurriculum
                    ? 'Open Curriculum to upload, preview, and publish for this unit'
                    : 'Ask an Academic Supervisor to publish materials for this unit',
        },
        {
            key: 'assign' as const,
            done: assignDone,
            note: !input.scopeSelected
                ? 'Pick a unit first'
                : assignDone
                  ? 'Student is assigned to this unit'
                  : 'Select a student and save the assignment',
        },
        {
            key: 'ready' as const,
            done: allDone,
            note: allDone
                ? 'The student can open AI Tutor and start a conversation'
                : 'Complete the steps above',
        },
    ];

    const firstOpen = allDone ? -1 : raw.findIndex((step) => !step.done);

    return raw.map((step, index) => {
        const meta = STEP_META[step.key];

        return {
            key: step.key,
            title: meta.title,
            shortTitle: meta.shortTitle,
            role: meta.role,
            state:
                allDone || step.done
                    ? 'done'
                    : index === firstOpen
                      ? 'current'
                      : 'upcoming',
            note: step.note,
        };
    });
}

function StepDoneBadge() {
    return (
        <svg
            aria-hidden
            viewBox="0 0 16 16"
            fill="none"
            className="absolute -end-1 -top-1 size-4 text-green-500"
        >
            <path
                d="M15.33 8A7.33 7.33 0 1 1 .67 8a7.33 7.33 0 0 1 14.66 0Zm-7.55 2.72 4.4-4.4a.73.73 0 1 0-1.04-1.03L7.27 9.17 4.85 6.75a.73.73 0 0 0-1.04 1.03l2.93 2.94a.73.73 0 0 0 1.04 0Z"
                fill="currentColor"
            />
        </svg>
    );
}

function SetupStepPill({ step }: { step: SetupStep }) {
    const Icon = STEP_META[step.key].Icon;

    const pillTone =
        step.state === 'current'
            ? 'border-primary/10 bg-primary/10 font-medium text-primary'
            : step.state === 'done'
              ? 'border-border bg-muted/30 text-foreground'
              : 'border-border text-foreground';

    const iconTone =
        step.state === 'current' ? 'text-primary' : 'text-muted-foreground';

    return (
        <div
            title={`${step.title} — ${step.role}`}
            aria-current={step.state === 'current' ? 'step' : undefined}
            className={`relative flex h-8.5 items-center gap-1.5 rounded-full border px-3 text-2sm leading-none whitespace-nowrap ${pillTone}`}
        >
            {step.state === 'done' && <StepDoneBadge />}
            <Icon aria-hidden className={`size-4 shrink-0 ${iconTone}`} />
            {step.shortTitle}
            <span className="sr-only">
                {step.state === 'done'
                    ? ' — completed'
                    : step.state === 'current'
                      ? ' — current step'
                      : ' — upcoming'}
            </span>
        </div>
    );
}

export function TutorSetupSteps({ input }: { input: SetupPipelineInput }) {
    const steps = deriveSetupSteps(input);
    const current = steps.find((step) => step.state === 'current');

    return (
        <section aria-label="Tutor setup progress">
            <ol className="flex flex-wrap items-center justify-center gap-x-2 gap-y-3 lg:flex-nowrap lg:gap-1.5">
                {steps.map((step, index) => (
                    <li key={step.key} className="flex items-center lg:gap-1.5">
                        {index > 0 && (
                            <span
                                aria-hidden
                                className="me-1.5 hidden h-px w-5 border-t border-dashed border-zinc-300 lg:block xl:w-9 dark:border-zinc-600"
                            />
                        )}
                        <SetupStepPill step={step} />
                    </li>
                ))}
            </ol>

            <div className="mt-4 space-y-1 text-center">
                {input.setupComplete ? (
                    <p className="inline-flex items-center gap-2 rounded-md bg-primary/10 px-3 py-2 text-sm text-primary">
                        <CheckCircle2 aria-hidden className="size-4" />
                        This student can use the AI Tutor for this unit.
                    </p>
                ) : (
                    current && (
                        <p className="text-sm">
                            <span className="font-medium text-mono">
                                {current.title}
                            </span>
                            <span className="text-muted-foreground">
                                {' '}
                                · {current.role}
                            </span>
                        </p>
                    )
                )}
                {!input.setupComplete && current?.note && (
                    <p className="text-xs text-muted-foreground">
                        {current.note}
                    </p>
                )}
            </div>
        </section>
    );
}
