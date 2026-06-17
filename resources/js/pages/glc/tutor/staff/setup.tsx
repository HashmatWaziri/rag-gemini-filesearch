import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { MetronicSelect, mapIdOptions } from '@/components/glc/metronic-select';
import GlcLayout from '@/layouts/glc-layout';
import { Head, Link, router } from '@inertiajs/react';
import { Check, ExternalLink, Loader2 } from 'lucide-react';
import { useEffect, useState } from 'react';
import { TutorSetupSteps } from './setup-steps';

interface UnitOption {
    id: number;
    name: string;
}

interface LevelOption {
    id: number;
    name: string;
    units: UnitOption[];
}

interface CourseOption {
    id: number;
    name: string;
    levels: LevelOption[];
}

interface Scope {
    course_id: number;
    course_level_id: number;
    course_unit_id: number;
    course: string;
    level: string;
    unit: string;
}

interface MaterialDocument {
    id: number;
    title: string;
    lesson: string | null;
    state: string;
    state_label: string;
    show_url: string;
}

interface MaterialsSummary {
    published_count: number;
    draft_count: number;
    pending_count: number;
    ready: boolean;
    documents: MaterialDocument[];
}

interface StudentRow {
    id: number;
    name: string;
    email: string;
    linked: boolean;
    consent: { required: boolean; confirmed: boolean };
    assignment: {
        course_id: number;
        course_level_id: number;
        course_unit_id: number;
        course: string;
        level: string;
        unit: string;
    } | null;
}

interface ScopeSelection {
    course_id: number | null;
    course_level_id: number | null;
    course_unit_id: number | null;
}

interface DraftSelection {
    courseId: number | '';
    levelId: number | '';
    unitId: number | '';
    studentIds: number[];
}

interface Props {
    canManageCurriculum: boolean;
    canViewAll: boolean;
    courses: CourseOption[];
    students: StudentRow[];
    scope: Scope | null;
    scopeSelection: ScopeSelection;
    selectedStudentIds: number[];
    materials: MaterialsSummary;
    assignmentMatchesScope: boolean;
    setupComplete: boolean;
    status: string | null;
}

function stateBadgeVariant(state: string): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (state === 'published') {
        return 'default';
    }

    if (state === 'draft' || state === 'publishing') {
        return 'secondary';
    }

    if (state === 'publish_failed') {
        return 'destructive';
    }

    return 'outline';
}

function buildSetupUrl(params: {
    courseId?: number | '';
    levelId?: number | '';
    unitId?: number | '';
    studentIds?: number[];
}): string {
    const query = new URLSearchParams();

    if (params.courseId) {
        query.set('course_id', String(params.courseId));
    }

    if (params.levelId) {
        query.set('course_level_id', String(params.levelId));
    }

    if (params.unitId) {
        query.set('course_unit_id', String(params.unitId));
    }

    for (const studentId of params.studentIds ?? []) {
        query.append('student_ids[]', String(studentId));
    }

    const qs = query.toString();

    return qs ? `/staff/tutor-setup?${qs}` : '/staff/tutor-setup';
}

function buildCurriculumUrl(scope: Scope): string {
    const query = new URLSearchParams({
        course_id: String(scope.course_id),
        course_level_id: String(scope.course_level_id),
        course_unit_id: String(scope.course_unit_id),
    });

    return `/staff/curriculum?${query.toString()}`;
}

function committedDraft(
    scopeSelection: ScopeSelection,
    selectedStudentIds: number[],
): DraftSelection {
    return {
        courseId: scopeSelection.course_id ?? '',
        levelId: scopeSelection.course_level_id ?? '',
        unitId: scopeSelection.course_unit_id ?? '',
        studentIds: selectedStudentIds,
    };
}

function sameStudentIds(left: number[], right: number[]): boolean {
    if (left.length !== right.length) {
        return false;
    }

    const sortedLeft = [...left].sort((a, b) => a - b);
    const sortedRight = [...right].sort((a, b) => a - b);

    return sortedLeft.every((id, index) => id === sortedRight[index]);
}

function draftIsDirty(
    draft: DraftSelection,
    committed: DraftSelection,
): boolean {
    return (
        draft.courseId !== committed.courseId ||
        draft.levelId !== committed.levelId ||
        draft.unitId !== committed.unitId ||
        !sameStudentIds(draft.studentIds, committed.studentIds)
    );
}

function scopeDraftIsDirty(
    draft: DraftSelection,
    committed: DraftSelection,
): boolean {
    return (
        draft.courseId !== committed.courseId ||
        draft.levelId !== committed.levelId ||
        draft.unitId !== committed.unitId
    );
}

function studentMatchesScope(
    student: StudentRow,
    scope: Scope,
): boolean {
    if (!student.assignment) {
        return false;
    }

    return (
        student.assignment.course_id === scope.course_id &&
        student.assignment.course_level_id === scope.course_level_id &&
        student.assignment.course_unit_id === scope.course_unit_id
    );
}

export default function TutorSetup({
    canManageCurriculum,
    courses,
    students,
    scope,
    scopeSelection,
    selectedStudentIds,
    materials,
    assignmentMatchesScope,
    setupComplete,
    status,
}: Props) {
    const committed = committedDraft(scopeSelection, selectedStudentIds);
    const [draft, setDraft] = useState<DraftSelection>(committed);
    const [saving, setSaving] = useState(false);
    const [assigning, setAssigning] = useState(false);

    useEffect(() => {
        setDraft(committedDraft(scopeSelection, selectedStudentIds));
    }, [
        scopeSelection.course_id,
        scopeSelection.course_level_id,
        scopeSelection.course_unit_id,
        selectedStudentIds,
    ]);

    const course = courses.find((option) => option.id === draft.courseId);
    const level = course?.levels.find((option) => option.id === draft.levelId);
    const isDirty = draftIsDirty(draft, committed);
    const scopeDirty = scopeDraftIsDirty(draft, committed);
    const draftScopeComplete =
        draft.courseId !== '' &&
        draft.levelId !== '' &&
        draft.unitId !== '';

    const selectedStudents = students.filter((student) =>
        selectedStudentIds.includes(student.id),
    );

    const assignableStudents = students.filter((student) => student.linked);

    const studentsNeedingAssignment =
        scope === null
            ? []
            : selectedStudents.filter(
                  (student) => !studentMatchesScope(student, scope),
              );

    const saveSelection = () => {
        setSaving(true);
        router.get(
            buildSetupUrl({
                courseId: draft.courseId,
                levelId: draft.levelId,
                unitId: draft.unitId,
                studentIds: draft.studentIds,
            }),
            {},
            {
                preserveScroll: true,
                onFinish: () => setSaving(false),
            },
        );
    };

    const assignStudents = () => {
        if (!scope || studentsNeedingAssignment.length === 0 || isDirty) {
            return;
        }

        setAssigning(true);
        router.put(
            '/staff/students/assignments',
            {
                student_ids: studentsNeedingAssignment.map(
                    (student) => student.id,
                ),
                course_id: scope.course_id,
                course_level_id: scope.course_level_id,
                course_unit_id: scope.course_unit_id,
            },
            {
                preserveScroll: true,
                onFinish: () => setAssigning(false),
            },
        );
    };

    const selectedStudentSummary =
        selectedStudents.length === 0
            ? 'Not selected'
            : selectedStudents.length === 1
              ? selectedStudents[0].name
              : `${selectedStudents.length} students selected`;

    const pipelineInput = {
        scopeSelected: scope !== null,
        materialsReady: materials.ready,
        assignmentMatchesScope,
        setupComplete,
        canManageCurriculum,
    };

    return (
        <GlcLayout title="Tutor Setup">
            <Head title="Tutor Setup" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-xl font-semibold text-mono">
                        Get a student ready for the AI Tutor
                    </h1>
                    <p className="mt-1 max-w-2xl text-sm text-secondary-foreground">
                        Confirm materials are live for a unit, then assign
                        students to that unit. Content is managed in
                        Curriculum; the tutor only answers from published
                        materials in the assigned scope.
                    </p>
                </div>

                {status && (
                    <div className="rounded-md border border-primary/20 bg-primary/10 px-4 py-3 text-sm text-primary">
                        {status}
                    </div>
                )}

                <TutorSetupSteps input={pipelineInput} />

                {scope && (
                    <div className="rounded-lg border border-border bg-muted/30 px-5 py-3.5">
                        <dl className="flex flex-wrap gap-x-10 gap-y-3">
                            {(
                                [
                                    ['Course', scope.course],
                                    ['Level', scope.level],
                                    ['Unit', scope.unit],
                                    [
                                        'Materials live',
                                        String(materials.published_count),
                                    ],
                                    ['Students', selectedStudentSummary],
                                ] as const
                            ).map(([label, value]) => (
                                <div key={label} className="min-w-0">
                                    <dt className="text-xs text-secondary-foreground">
                                        {label}
                                    </dt>
                                    <dd className="mt-0.5 text-sm font-medium break-words text-mono">
                                        {value}
                                    </dd>
                                </div>
                            ))}
                        </dl>
                    </div>
                )}

                <div className="flex flex-col items-start gap-5 lg:flex-row lg:gap-7.5">
                    <div className="w-full min-w-0 flex-1 space-y-5">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">
                                    1. Choose the unit scope
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="grid gap-3 sm:grid-cols-3">
                                    <div>
                                        <Label className="text-xs font-medium text-muted-foreground">
                                            Course
                                        </Label>
                                        <MetronicSelect
                                            selectKey="course-select"
                                            className="mt-1"
                                            value={
                                                draft.courseId
                                                    ? String(draft.courseId)
                                                    : null
                                            }
                                            onChange={(value) =>
                                                setDraft({
                                                    courseId: value
                                                        ? Number(value)
                                                        : '',
                                                    levelId: '',
                                                    unitId: '',
                                                    studentIds:
                                                        draft.studentIds,
                                                })
                                            }
                                            options={mapIdOptions(courses)}
                                            placeholder="Select course"
                                            isSearchable={false}
                                        />
                                    </div>

                                    <div>
                                        <Label className="text-xs font-medium text-muted-foreground">
                                            Level
                                        </Label>
                                        <MetronicSelect
                                            selectKey={`level-select-${draft.courseId || 'none'}`}
                                            className="mt-1"
                                            value={
                                                draft.levelId
                                                    ? String(draft.levelId)
                                                    : null
                                            }
                                            onChange={(value) =>
                                                setDraft({
                                                    ...draft,
                                                    levelId: value
                                                        ? Number(value)
                                                        : '',
                                                    unitId: '',
                                                })
                                            }
                                            options={mapIdOptions(
                                                course?.levels ?? [],
                                            )}
                                            placeholder="Select level"
                                            disabled={!course}
                                            isSearchable={false}
                                        />
                                    </div>

                                    <div>
                                        <Label className="text-xs font-medium text-muted-foreground">
                                            Unit
                                        </Label>
                                        <MetronicSelect
                                            selectKey={`unit-select-${draft.levelId || 'none'}`}
                                            className="mt-1"
                                            value={
                                                draft.unitId
                                                    ? String(draft.unitId)
                                                    : null
                                            }
                                            onChange={(value) =>
                                                setDraft({
                                                    ...draft,
                                                    unitId: value
                                                        ? Number(value)
                                                        : '',
                                                })
                                            }
                                            options={mapIdOptions(
                                                level?.units ?? [],
                                            )}
                                            placeholder="Select unit"
                                            disabled={!level}
                                            isSearchable={false}
                                        />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">
                                    2. Materials readiness for this unit
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {scopeDirty && draftScopeComplete ? (
                                    <p className="text-sm text-muted-foreground">
                                        Save your selection below to load
                                        materials for this unit.
                                    </p>
                                ) : !scope ? (
                                    <p className="text-sm text-muted-foreground">
                                        Select a course, level, and unit, then
                                        save to see materials.
                                    </p>
                                ) : (
                                    <>
                                        <div className="flex flex-wrap gap-2">
                                            <Badge
                                                variant={
                                                    materials.ready
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                            >
                                                {materials.published_count}{' '}
                                                live for tutor
                                            </Badge>
                                            {materials.draft_count > 0 && (
                                                <Badge variant="outline">
                                                    {materials.draft_count}{' '}
                                                    draft
                                                    {materials.draft_count === 1
                                                        ? ''
                                                        : 's'}
                                                </Badge>
                                            )}
                                            {materials.pending_count > 0 && (
                                                <Badge variant="outline">
                                                    {materials.pending_count}{' '}
                                                    preparing
                                                </Badge>
                                            )}
                                        </div>

                                        {materials.documents.length === 0 ? (
                                            <p className="mt-3 text-sm text-muted-foreground">
                                                No documents tagged to this
                                                unit yet.
                                            </p>
                                        ) : (
                                            <ul className="mt-3 space-y-2">
                                                {materials.documents.map(
                                                    (document) => (
                                                        <li
                                                            key={document.id}
                                                            className="flex flex-wrap items-center justify-between gap-2 rounded-md border border-border px-3 py-2"
                                                        >
                                                            <div className="min-w-0">
                                                                <p className="text-sm font-medium text-mono">
                                                                    {
                                                                        document.title
                                                                    }
                                                                </p>
                                                                <p className="text-xs text-muted-foreground">
                                                                    {document.lesson ??
                                                                        'Whole unit'}{' '}
                                                                    ·{' '}
                                                                    {
                                                                        document.state_label
                                                                    }
                                                                </p>
                                                            </div>
                                                            <div className="flex items-center gap-2">
                                                                <Badge
                                                                    variant={stateBadgeVariant(
                                                                        document.state,
                                                                    )}
                                                                >
                                                                    {
                                                                        document.state_label
                                                                    }
                                                                </Badge>
                                                                {canManageCurriculum && (
                                                                    <a
                                                                        href={
                                                                            document.show_url
                                                                        }
                                                                        className="inline-flex items-center gap-1 text-xs font-medium text-primary hover:underline"
                                                                    >
                                                                        Open
                                                                        <ExternalLink className="size-3" />
                                                                    </a>
                                                                )}
                                                            </div>
                                                        </li>
                                                    ),
                                                )}
                                            </ul>
                                        )}

                                        {!materials.ready &&
                                            !canManageCurriculum && (
                                                <p className="mt-3 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                                                    Ask an Academic Supervisor
                                                    to upload and publish
                                                    materials for this unit
                                                    before assigning students.
                                                </p>
                                            )}

                                        {canManageCurriculum && (
                                            <p className="mt-3 text-sm text-muted-foreground">
                                                Upload, preview, and publish
                                                documents in{' '}
                                                <Link
                                                    href={buildCurriculumUrl(
                                                        scope,
                                                    )}
                                                    className="font-medium text-primary hover:underline"
                                                >
                                                    Curriculum
                                                </Link>
                                                {materials.ready
                                                    ? ' when you need to add or update materials for this unit.'
                                                    : ' before assigning students here.'}
                                            </p>
                                        )}
                                    </>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">
                                    3. Assign students
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {!scope && !draftScopeComplete ? (
                                    <p className="text-sm text-muted-foreground">
                                        Choose a unit scope first.
                                    </p>
                                ) : (
                                    <>
                                        <div>
                                            <Label className="text-xs font-medium text-muted-foreground">
                                                Students
                                            </Label>
                                            <MetronicSelect
                                                isMulti
                                                className="mt-1"
                                                value={draft.studentIds.map(
                                                    String,
                                                )}
                                                onChange={(values) =>
                                                    setDraft({
                                                        ...draft,
                                                        studentIds:
                                                            values.map(Number),
                                                    })
                                                }
                                                options={assignableStudents.map(
                                                    (student) => ({
                                                        value: String(
                                                            student.id,
                                                        ),
                                                        label: `${student.name} (${student.email})`,
                                                    }),
                                                )}
                                                placeholder="Select students"
                                                isSearchable={false}
                                            />
                                        </div>

                                        {assignableStudents.length === 0 && (
                                            <p className="text-sm text-amber-800">
                                                Link students on{' '}
                                                <Link
                                                    href="/staff/students"
                                                    className="font-medium text-primary hover:underline"
                                                >
                                                    My Students
                                                </Link>{' '}
                                                before assigning them here.
                                            </p>
                                        )}

                                        <div className="flex flex-wrap items-center gap-3 border-t border-border pt-4">
                                            <Button
                                                type="button"
                                                onClick={saveSelection}
                                                disabled={!isDirty || saving}
                                            >
                                                {saving && (
                                                    <Loader2 className="mr-2 size-4 animate-spin" />
                                                )}
                                                Save selection
                                            </Button>

                                            {isDirty && (
                                                <p className="text-xs text-muted-foreground">
                                                    Unsaved changes — save
                                                    before assigning students.
                                                </p>
                                            )}
                                        </div>

                                        {scope &&
                                            materials.ready &&
                                            !isDirty &&
                                            selectedStudents.length > 0 && (
                                                <div className="space-y-3 border-t border-border pt-4">
                                                    {selectedStudents.some(
                                                        (student) =>
                                                            student.consent
                                                                .required &&
                                                            !student.consent
                                                                .confirmed,
                                                    ) && (
                                                        <p className="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                                                            Guardian consent is
                                                            still required for
                                                            some selected
                                                            students. An Admin
                                                            must confirm
                                                            consent before they
                                                            can use the tutor.
                                                        </p>
                                                    )}

                                                    {studentsNeedingAssignment.length ===
                                                    0 ? (
                                                        <p className="inline-flex items-center gap-2 text-sm text-primary">
                                                            <Check className="size-4" />
                                                            {selectedStudents.length ===
                                                            1
                                                                ? `${selectedStudents[0].name} is assigned to this unit.`
                                                                : `All ${selectedStudents.length} selected students are assigned to this unit.`}
                                                        </p>
                                                    ) : (
                                                        <Button
                                                            type="button"
                                                            onClick={
                                                                assignStudents
                                                            }
                                                            disabled={assigning}
                                                        >
                                                            {assigning && (
                                                                <Loader2 className="mr-2 size-4 animate-spin" />
                                                            )}
                                                            Assign{' '}
                                                            {
                                                                studentsNeedingAssignment.length
                                                            }{' '}
                                                            student
                                                            {studentsNeedingAssignment.length ===
                                                            1
                                                                ? ''
                                                                : 's'}{' '}
                                                            to this unit
                                                        </Button>
                                                    )}
                                                </div>
                                            )}

                                        {scope &&
                                            !materials.ready &&
                                            !isDirty &&
                                            selectedStudents.length > 0 && (
                                                <p className="text-sm text-muted-foreground">
                                                    Publish at least one live
                                                    document for this unit
                                                    before assigning students.
                                                </p>
                                            )}
                                    </>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    <aside className="w-full shrink-0 lg:sticky lg:top-24 lg:w-[330px]">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">
                                    Setup summary
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3 text-sm">
                                <div className="flex items-start gap-2">
                                    <Check
                                        className={`mt-0.5 size-4 shrink-0 ${scope ? 'text-green-500' : 'text-muted-foreground'}`}
                                    />
                                    <span>
                                        Unit scope{' '}
                                        {scope
                                            ? 'selected'
                                            : 'not selected'}
                                    </span>
                                </div>
                                <div className="flex items-start gap-2">
                                    <Check
                                        className={`mt-0.5 size-4 shrink-0 ${materials.ready ? 'text-green-500' : 'text-muted-foreground'}`}
                                    />
                                    <span>
                                        {materials.ready
                                            ? `${materials.published_count} document(s) live for the tutor`
                                            : 'No live materials for this unit'}
                                    </span>
                                </div>
                                <div className="flex items-start gap-2">
                                    <Check
                                        className={`mt-0.5 size-4 shrink-0 ${assignmentMatchesScope ? 'text-green-500' : 'text-muted-foreground'}`}
                                    />
                                    <span>
                                        {assignmentMatchesScope
                                            ? selectedStudents.length <= 1
                                                ? 'Student assigned to this unit'
                                                : 'All selected students assigned to this unit'
                                            : selectedStudents.length > 0
                                              ? 'Some selected students still need assignment'
                                              : 'No students selected'}
                                    </span>
                                </div>

                                {setupComplete && selectedStudents.length > 0 && (
                                    <div className="rounded-md border border-primary/20 bg-primary/5 px-3 py-2 text-primary">
                                        <p className="font-medium">
                                            Ready for{' '}
                                            {selectedStudents.length === 1
                                                ? selectedStudents[0].name
                                                : `${selectedStudents.length} students`}
                                        </p>
                                        <p className="mt-1 text-xs text-secondary-foreground">
                                            They can open AI Tutor and start
                                            practising.
                                        </p>
                                        <Link
                                            href="/staff/tutor"
                                            className="mt-2 inline-block text-xs font-medium hover:underline"
                                        >
                                            View tutor activity →
                                        </Link>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </aside>
                </div>
            </div>
        </GlcLayout>
    );
}
