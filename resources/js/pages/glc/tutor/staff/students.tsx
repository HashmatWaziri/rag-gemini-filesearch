import { GlcDataTableCard } from '@/components/glc';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { MetronicSelect, mapIdOptions } from '@/components/glc/metronic-select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import GlcLayout from '@/layouts/glc-layout';
import { Head, Link, router } from '@inertiajs/react';
import { Fragment, useState } from 'react';

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
            <Badge variant="outline" className="text-muted-foreground">
                Consent not required
            </Badge>
        );
    }

    return consent.confirmed ? (
        <Badge>Guardian consent confirmed</Badge>
    ) : (
        <Badge variant="destructive">Guardian consent required</Badge>
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

    return (
        <div className="mt-3 grid gap-2 rounded-md border border-border bg-muted/50 p-3 sm:grid-cols-4">
            <MetronicSelect
                aria-label="Course"
                value={courseId ? String(courseId) : null}
                onChange={(value) => {
                    setCourseId(value ? Number(value) : '');
                    setLevelId('');
                    setUnitId('');
                }}
                options={mapIdOptions(courses)}
                placeholder="Course..."
                isSearchable={false}
            />
            <MetronicSelect
                aria-label="Level"
                value={levelId ? String(levelId) : null}
                onChange={(value) => {
                    setLevelId(value ? Number(value) : '');
                    setUnitId('');
                }}
                options={mapIdOptions(course?.levels ?? [])}
                placeholder="Level..."
                disabled={!course}
                isSearchable={false}
            />
            <MetronicSelect
                aria-label="Unit"
                value={unitId ? String(unitId) : null}
                onChange={(value) =>
                    setUnitId(value ? Number(value) : '')
                }
                options={mapIdOptions(level?.units ?? [])}
                placeholder="Unit..."
                disabled={!level}
                isSearchable={false}
            />
            <div className="flex gap-2">
                <Button
                    type="button"
                    className="flex-1"
                    onClick={save}
                    disabled={saving || !courseId || !levelId || !unitId}
                >
                    Save
                </Button>
                <Button type="button" variant="outline" onClick={onDone}>
                    Cancel
                </Button>
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

            <p className="mb-4 text-sm text-secondary-foreground">
                {canViewAll
                    ? 'All enrolled students. Set each student\u2019s course, level, and unit so the AI Tutor uses the right materials.'
                    : 'Students linked to you. Set each student\u2019s course, level, and unit so the AI Tutor uses the right materials.'}
            </p>

            {linkableStudents.length > 0 && (
                <Card className="mb-6">
                    <CardHeader>
                        <CardTitle className="text-base">
                            Link a student
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <MetronicSelect
                            id="link-student"
                            className="min-w-0 flex-1"
                            value={
                                linkStudentId
                                    ? String(linkStudentId)
                                    : null
                            }
                            onChange={(value) =>
                                setLinkStudentId(value ? Number(value) : '')
                            }
                            options={linkableStudents.map((student) => ({
                                value: String(student.id),
                                label: `${student.name} (${student.email})`,
                            }))}
                            placeholder="Choose a student..."
                            isSearchable={false}
                        />
                        <Button
                            type="button"
                            onClick={linkStudent}
                            disabled={!linkStudentId}
                        >
                            Link
                        </Button>
                    </CardContent>
                </Card>
            )}

            {students.length === 0 ? (
                <p className="rounded-xl border border-border bg-card px-5 py-8 text-center text-sm text-muted-foreground">
                    No students yet. Link a student to get started.
                </p>
            ) : (
                <GlcDataTableCard>
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-muted/50 hover:bg-muted/50">
                                <TableHead className="text-xs uppercase">
                                    Student
                                </TableHead>
                                <TableHead className="text-xs uppercase">
                                    Consent
                                </TableHead>
                                <TableHead className="text-xs uppercase">
                                    Course assignment
                                </TableHead>
                                <TableHead className="text-xs uppercase">
                                    <span className="sr-only">Actions</span>
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {students.map((student) => (
                                <Fragment key={student.id}>
                                    <TableRow>
                                        <TableCell className="align-top">
                                            <p className="font-medium text-mono">
                                                {student.name}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {student.email}
                                                {student.age !== null &&
                                                    ` · age ${student.age}`}
                                            </p>
                                        </TableCell>
                                        <TableCell className="align-top">
                                            <ConsentBadge
                                                consent={student.consent}
                                            />
                                        </TableCell>
                                        <TableCell className="align-top">
                                            {student.assignment ? (
                                                <span className="inline-flex rounded-md bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary">
                                                    {student.assignment.course} /{' '}
                                                    {student.assignment.level} /{' '}
                                                    {student.assignment.unit}
                                                </span>
                                            ) : (
                                                <span className="inline-flex rounded-md bg-amber-500/10 px-2.5 py-1 text-xs font-medium text-amber-700 dark:text-amber-400">
                                                    No course set yet
                                                </span>
                                            )}
                                        </TableCell>
                                        <TableCell className="align-top">
                                            <div className="flex flex-wrap justify-end gap-2">
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() =>
                                                        setEditingId(
                                                            editingId ===
                                                                student.id
                                                                ? null
                                                                : student.id,
                                                        )
                                                    }
                                                >
                                                    {student.assignment
                                                        ? 'Change course'
                                                        : 'Set course'}
                                                </Button>
                                                {student.linked ? (
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        className="text-destructive hover:text-destructive"
                                                        onClick={() =>
                                                            router.delete(
                                                                `/staff/students/${student.id}/link`,
                                                                {
                                                                    preserveScroll: true,
                                                                },
                                                            )
                                                        }
                                                    >
                                                        Unlink
                                                    </Button>
                                                ) : (
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() =>
                                                            router.post(
                                                                `/staff/students/${student.id}/link`,
                                                                {},
                                                                {
                                                                    preserveScroll: true,
                                                                },
                                                            )
                                                        }
                                                    >
                                                        Link to me
                                                    </Button>
                                                )}
                                                <Link
                                                    href={`/staff/tutor/students/${student.id}`}
                                                >
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                    >
                                                        View activity
                                                    </Button>
                                                </Link>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                    {editingId === student.id && (
                                        <TableRow key={`${student.id}-edit`}>
                                            <TableCell colSpan={4}>
                                                <AssignmentForm
                                                    student={student}
                                                    courses={courses}
                                                    onDone={() =>
                                                        setEditingId(null)
                                                    }
                                                />
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </Fragment>
                            ))}
                        </TableBody>
                    </Table>
                </GlcDataTableCard>
            )}
        </GlcLayout>
    );
}
