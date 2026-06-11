import GlcLayout from '@/layouts/glc-layout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

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

interface StudentAssignment {
    course_id: number;
    course_level_id: number;
    course_unit_id: number;
    course: string;
    level: string;
    unit: string;
}

interface StudentRow {
    id: number;
    name: string;
    email: string;
    age: number | null;
    linked: boolean;
    consent: { required: boolean; confirmed: boolean };
    assignment: StudentAssignment | null;
}

interface Props {
    students: StudentRow[];
    linkableStudents: { id: number; name: string; email: string }[];
    courses: CourseOption[];
    canViewAll: boolean;
}

function ConsentBadge({
    consent,
}: {
    consent: { required: boolean; confirmed: boolean };
}) {
    if (!consent.required) {
        return (
            <span className="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">
                Consent not required
            </span>
        );
    }

    return consent.confirmed ? (
        <span className="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700">
            Guardian consent confirmed
        </span>
    ) : (
        <span className="rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-700">
            Guardian consent required
        </span>
    );
}

function AssignmentForm({
    student,
    courses,
    onDone,
}: {
    student: StudentRow;
    courses: CourseOption[];
    onDone: () => void;
}) {
    const [courseId, setCourseId] = useState<number | ''>(
        student.assignment?.course_id ?? '',
    );
    const [levelId, setLevelId] = useState<number | ''>(
        student.assignment?.course_level_id ?? '',
    );
    const [unitId, setUnitId] = useState<number | ''>(
        student.assignment?.course_unit_id ?? '',
    );
    const [saving, setSaving] = useState(false);

    const course = courses.find((option) => option.id === courseId);
    const level = course?.levels.find((option) => option.id === levelId);

    const save = () => {
        if (!courseId || !levelId || !unitId) {
            return;
        }

        setSaving(true);
        router.put(
            `/staff/students/${student.id}/assignment`,
            {
                course_id: courseId,
                course_level_id: levelId,
                course_unit_id: unitId,
            },
            {
                preserveScroll: true,
                onFinish: () => {
                    setSaving(false);
                    onDone();
                },
            },
        );
    };

    const selectClasses =
        'w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm focus:border-emerald-500 focus:outline-none';

    return (
        <div className="mt-3 grid gap-2 rounded-md border border-slate-200 bg-slate-50 p-3 sm:grid-cols-4">
            <select
                value={courseId}
                onChange={(event) => {
                    setCourseId(Number(event.target.value) || '');
                    setLevelId('');
                    setUnitId('');
                }}
                className={selectClasses}
                aria-label="Course"
            >
                <option value="">Course...</option>
                {courses.map((option) => (
                    <option key={option.id} value={option.id}>
                        {option.name}
                    </option>
                ))}
            </select>
            <select
                value={levelId}
                onChange={(event) => {
                    setLevelId(Number(event.target.value) || '');
                    setUnitId('');
                }}
                disabled={!course}
                className={selectClasses}
                aria-label="Level"
            >
                <option value="">Level...</option>
                {course?.levels.map((option) => (
                    <option key={option.id} value={option.id}>
                        {option.name}
                    </option>
                ))}
            </select>
            <select
                value={unitId}
                onChange={(event) =>
                    setUnitId(Number(event.target.value) || '')
                }
                disabled={!level}
                className={selectClasses}
                aria-label="Unit"
            >
                <option value="">Unit...</option>
                {level?.units.map((option) => (
                    <option key={option.id} value={option.id}>
                        {option.name}
                    </option>
                ))}
            </select>
            <div className="flex gap-2">
                <button
                    type="button"
                    onClick={save}
                    disabled={saving || !courseId || !levelId || !unitId}
                    className="flex-1 rounded-md bg-emerald-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-emerald-500 disabled:opacity-50"
                >
                    Save
                </button>
                <button
                    type="button"
                    onClick={onDone}
                    className="rounded-md border border-slate-300 px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-100"
                >
                    Cancel
                </button>
            </div>
        </div>
    );
}

export default function StaffStudents({
    students,
    linkableStudents,
    courses,
    canViewAll,
}: Props) {
    const [editingId, setEditingId] = useState<number | null>(null);
    const [linkStudentId, setLinkStudentId] = useState<number | ''>('');

    const linkStudent = () => {
        if (!linkStudentId) {
            return;
        }

        router.post(
            `/staff/students/${linkStudentId}/link`,
            {},
            {
                preserveScroll: true,
                onFinish: () => setLinkStudentId(''),
            },
        );
    };

    return (
        <GlcLayout title="My Students">
            <Head title="My Students" />

            <p className="mb-4 text-sm text-slate-600">
                {canViewAll
                    ? 'All enrolled students. Set each student\u2019s course, level, and unit so the AI Tutor uses the right materials.'
                    : 'Students linked to you. Set each student\u2019s course, level, and unit so the AI Tutor uses the right materials.'}
            </p>

            {linkableStudents.length > 0 && (
                <div className="mb-6 flex flex-col gap-2 rounded-lg border border-slate-200 bg-white p-4 sm:flex-row sm:items-center">
                    <label
                        htmlFor="link-student"
                        className="text-sm font-medium text-slate-700"
                    >
                        Link a student to me:
                    </label>
                    <select
                        id="link-student"
                        value={linkStudentId}
                        onChange={(event) =>
                            setLinkStudentId(Number(event.target.value) || '')
                        }
                        className="min-w-0 flex-1 rounded-md border border-slate-300 px-2 py-1.5 text-sm focus:border-emerald-500 focus:outline-none"
                    >
                        <option value="">Choose a student...</option>
                        {linkableStudents.map((student) => (
                            <option key={student.id} value={student.id}>
                                {student.name} ({student.email})
                            </option>
                        ))}
                    </select>
                    <button
                        type="button"
                        onClick={linkStudent}
                        disabled={!linkStudentId}
                        className="rounded-md bg-emerald-600 px-4 py-1.5 text-sm font-semibold text-white hover:bg-emerald-500 disabled:opacity-50"
                    >
                        Link
                    </button>
                </div>
            )}

            {students.length === 0 ? (
                <p className="rounded-lg border border-slate-200 bg-white p-4 text-sm text-slate-500">
                    No students yet. Link a student to get started.
                </p>
            ) : (
                <ul className="space-y-3">
                    {students.map((student) => (
                        <li
                            key={student.id}
                            className="rounded-lg border border-slate-200 bg-white p-4"
                        >
                            <div className="flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    <p className="text-sm font-semibold text-slate-900">
                                        {student.name}
                                    </p>
                                    <p className="text-xs text-slate-500">
                                        {student.email}
                                        {student.age !== null &&
                                            ` (age ${student.age})`}
                                    </p>
                                </div>
                                <ConsentBadge consent={student.consent} />
                            </div>

                            <div className="mt-3 flex flex-wrap items-center gap-2">
                                {student.assignment ? (
                                    <span className="rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-800">
                                        {student.assignment.course} /{' '}
                                        {student.assignment.level} /{' '}
                                        {student.assignment.unit}
                                    </span>
                                ) : (
                                    <span className="rounded-md bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-800">
                                        No course set yet
                                    </span>
                                )}

                                <button
                                    type="button"
                                    onClick={() =>
                                        setEditingId(
                                            editingId === student.id
                                                ? null
                                                : student.id,
                                        )
                                    }
                                    className="rounded-md border border-slate-300 px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                >
                                    {student.assignment
                                        ? 'Change course'
                                        : 'Set course'}
                                </button>

                                {student.linked ? (
                                    <button
                                        type="button"
                                        onClick={() =>
                                            router.delete(
                                                `/staff/students/${student.id}/link`,
                                                { preserveScroll: true },
                                            )
                                        }
                                        className="rounded-md border border-red-200 px-2.5 py-1 text-xs font-medium text-red-600 hover:bg-red-50"
                                    >
                                        Unlink from me
                                    </button>
                                ) : (
                                    <button
                                        type="button"
                                        onClick={() =>
                                            router.post(
                                                `/staff/students/${student.id}/link`,
                                                {},
                                                { preserveScroll: true },
                                            )
                                        }
                                        className="rounded-md border border-slate-300 px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                    >
                                        Link to me
                                    </button>
                                )}
                            </div>

                            {editingId === student.id && (
                                <AssignmentForm
                                    student={student}
                                    courses={courses}
                                    onDone={() => setEditingId(null)}
                                />
                            )}
                        </li>
                    ))}
                </ul>
            )}
        </GlcLayout>
    );
}
