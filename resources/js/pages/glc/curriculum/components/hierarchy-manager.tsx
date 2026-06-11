import { router } from '@inertiajs/react';
import { useState } from 'react';
import { inputClass, type TreeCourse } from './types';

interface HierarchyManagerProps {
    tree: TreeCourse[];
}

interface ItemRowProps {
    id: number;
    name: string;
    position?: number;
    selected: boolean;
    onSelect?: () => void;
    updateUrl: string;
    deleteUrl: string;
    deleteWarning: string;
    hasPosition: boolean;
}

const itemButtonClass = (selected: boolean) =>
    `flex-1 truncate rounded px-2 py-1 text-left text-sm ${
        selected
            ? 'bg-emerald-50 font-medium text-emerald-700'
            : 'text-slate-700 hover:bg-slate-50'
    }`;

function ItemRow({
    id,
    name,
    position,
    selected,
    onSelect,
    updateUrl,
    deleteUrl,
    deleteWarning,
    hasPosition,
}: ItemRowProps) {
    const [editing, setEditing] = useState(false);
    const [editName, setEditName] = useState(name);
    const [editPosition, setEditPosition] = useState(String(position ?? 0));

    const save = () => {
        router.put(
            updateUrl,
            hasPosition
                ? { name: editName, position: Number(editPosition) }
                : { name: editName },
            { preserveScroll: true, onSuccess: () => setEditing(false) },
        );
    };

    const remove = () => {
        if (confirm(deleteWarning)) {
            router.delete(deleteUrl, { preserveScroll: true });
        }
    };

    if (editing) {
        return (
            <div className="flex items-center gap-1 py-0.5">
                <input
                    type="text"
                    className={`${inputClass} py-1`}
                    value={editName}
                    onChange={(e) => setEditName(e.target.value)}
                    aria-label="Name"
                />
                {hasPosition && (
                    <input
                        type="number"
                        min={0}
                        className={`${inputClass} w-16 py-1`}
                        value={editPosition}
                        onChange={(e) => setEditPosition(e.target.value)}
                        aria-label="Position"
                    />
                )}
                <button
                    type="button"
                    onClick={save}
                    className="rounded px-1.5 py-1 text-xs font-medium text-emerald-700 hover:bg-emerald-50"
                >
                    Save
                </button>
                <button
                    type="button"
                    onClick={() => setEditing(false)}
                    className="rounded px-1.5 py-1 text-xs text-slate-500 hover:bg-slate-100"
                >
                    Cancel
                </button>
            </div>
        );
    }

    return (
        <div className="group flex items-center gap-1 py-0.5" data-id={id}>
            <button
                type="button"
                onClick={onSelect}
                className={itemButtonClass(selected)}
            >
                {name}
            </button>
            <button
                type="button"
                onClick={() => setEditing(true)}
                className="rounded px-1.5 py-1 text-xs text-slate-400 hover:bg-slate-100 hover:text-slate-700"
            >
                Edit
            </button>
            <button
                type="button"
                onClick={remove}
                className="rounded px-1.5 py-1 text-xs text-slate-400 hover:bg-red-50 hover:text-red-600"
            >
                Delete
            </button>
        </div>
    );
}

interface AddRowProps {
    placeholder: string;
    onAdd: (name: string) => void;
    disabled?: boolean;
    disabledHint?: string;
}

function AddRow({ placeholder, onAdd, disabled, disabledHint }: AddRowProps) {
    const [name, setName] = useState('');

    if (disabled) {
        return <p className="mt-2 text-xs text-slate-400">{disabledHint}</p>;
    }

    return (
        <form
            className="mt-2 flex items-center gap-1"
            onSubmit={(e) => {
                e.preventDefault();
                if (name.trim() !== '') {
                    onAdd(name.trim());
                    setName('');
                }
            }}
        >
            <input
                type="text"
                className={`${inputClass} py-1`}
                placeholder={placeholder}
                value={name}
                onChange={(e) => setName(e.target.value)}
            />
            <button
                type="submit"
                className="rounded-md border border-emerald-200 px-2 py-1 text-xs font-medium text-emerald-700 hover:bg-emerald-50"
            >
                Add
            </button>
        </form>
    );
}

/**
 * Inline CRUD over Course -> Level -> Unit -> Lesson. Select an item in a
 * column to manage its children in the next one. Deletes cascade down the
 * hierarchy (including tagged documents), hence the explicit confirm text.
 */
export default function HierarchyManager({ tree }: HierarchyManagerProps) {
    const [courseId, setCourseId] = useState<number | null>(null);
    const [levelId, setLevelId] = useState<number | null>(null);
    const [unitId, setUnitId] = useState<number | null>(null);

    const course = tree.find((c) => c.id === courseId) ?? null;
    const level = course?.levels.find((l) => l.id === levelId) ?? null;
    const unit = level?.units.find((u) => u.id === unitId) ?? null;

    const post = (url: string, data: Record<string, string | number>) =>
        router.post(url, data, { preserveScroll: true });

    const columnClass = 'rounded-md border border-slate-200 bg-slate-50/50 p-2';
    const headingClass =
        'mb-1 px-2 text-xs font-semibold uppercase tracking-wide text-slate-500';

    return (
        <section className="rounded-lg border border-slate-200 bg-white p-4">
            <h2 className="mb-1 text-sm font-semibold text-slate-800">
                Course hierarchy
            </h2>
            <p className="mb-3 text-xs text-slate-500">
                Manage courses, levels, units, and lessons. Select an item to
                manage what sits under it. Deleting an item removes everything
                beneath it, including tagged documents.
            </p>

            <div className="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-4">
                <div className={columnClass}>
                    <h3 className={headingClass}>Courses</h3>
                    {tree.map((c) => (
                        <ItemRow
                            key={c.id}
                            id={c.id}
                            name={c.name}
                            selected={c.id === courseId}
                            onSelect={() => {
                                setCourseId(c.id);
                                setLevelId(null);
                                setUnitId(null);
                            }}
                            updateUrl={`/staff/curriculum/courses/${c.id}`}
                            deleteUrl={`/staff/curriculum/courses/${c.id}`}
                            deleteWarning={`Delete course "${c.name}"? All of its levels, units, lessons, and documents will be deleted.`}
                            hasPosition={false}
                        />
                    ))}
                    {tree.length === 0 && (
                        <p className="px-2 py-1 text-xs text-slate-400">
                            No courses yet.
                        </p>
                    )}
                    <AddRow
                        placeholder="New course name"
                        onAdd={(name) =>
                            post('/staff/curriculum/courses', { name })
                        }
                    />
                </div>

                <div className={columnClass}>
                    <h3 className={headingClass}>
                        Levels{course ? ` — ${course.name}` : ''}
                    </h3>
                    {course?.levels.map((l) => (
                        <ItemRow
                            key={l.id}
                            id={l.id}
                            name={l.name}
                            position={l.position}
                            selected={l.id === levelId}
                            onSelect={() => {
                                setLevelId(l.id);
                                setUnitId(null);
                            }}
                            updateUrl={`/staff/curriculum/levels/${l.id}`}
                            deleteUrl={`/staff/curriculum/levels/${l.id}`}
                            deleteWarning={`Delete level "${l.name}"? All of its units, lessons, and documents will be deleted.`}
                            hasPosition
                        />
                    ))}
                    <AddRow
                        placeholder="New level name"
                        onAdd={(name) =>
                            course &&
                            post('/staff/curriculum/levels', {
                                course_id: course.id,
                                name,
                            })
                        }
                        disabled={!course}
                        disabledHint="Select a course to manage its levels."
                    />
                </div>

                <div className={columnClass}>
                    <h3 className={headingClass}>
                        Units{level ? ` — ${level.name}` : ''}
                    </h3>
                    {level?.units.map((u) => (
                        <ItemRow
                            key={u.id}
                            id={u.id}
                            name={u.name}
                            position={u.position}
                            selected={u.id === unitId}
                            onSelect={() => setUnitId(u.id)}
                            updateUrl={`/staff/curriculum/units/${u.id}`}
                            deleteUrl={`/staff/curriculum/units/${u.id}`}
                            deleteWarning={`Delete unit "${u.name}"? All of its lessons and documents will be deleted.`}
                            hasPosition
                        />
                    ))}
                    <AddRow
                        placeholder="New unit name"
                        onAdd={(name) =>
                            level &&
                            post('/staff/curriculum/units', {
                                course_level_id: level.id,
                                name,
                            })
                        }
                        disabled={!level}
                        disabledHint="Select a level to manage its units."
                    />
                </div>

                <div className={columnClass}>
                    <h3 className={headingClass}>
                        Lessons{unit ? ` — ${unit.name}` : ''}
                    </h3>
                    {unit?.lessons.map((l) => (
                        <ItemRow
                            key={l.id}
                            id={l.id}
                            name={l.name}
                            position={l.position}
                            selected={false}
                            updateUrl={`/staff/curriculum/lessons/${l.id}`}
                            deleteUrl={`/staff/curriculum/lessons/${l.id}`}
                            deleteWarning={`Delete lesson "${l.name}"? Documents tagged to it stay on the unit.`}
                            hasPosition
                        />
                    ))}
                    <AddRow
                        placeholder="New lesson name"
                        onAdd={(name) =>
                            unit &&
                            post('/staff/curriculum/lessons', {
                                course_unit_id: unit.id,
                                name,
                            })
                        }
                        disabled={!unit}
                        disabledHint="Select a unit to manage its lessons."
                    />
                </div>
            </div>
        </section>
    );
}
