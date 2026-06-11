import {
    emptySelection,
    inputClass,
    labelClass,
    type HierarchySelection,
    type TreeCourse,
} from './types';

interface HierarchyPickerProps {
    tree: TreeCourse[];
    value: HierarchySelection;
    onChange: (value: HierarchySelection) => void;
    /** Filter mode: every level may stay empty ("All ..."). */
    allowEmpty?: boolean;
    errors?: Partial<Record<keyof HierarchySelection, string>>;
}

/**
 * Dependent Course -> Level -> Unit -> Lesson selects fed by the full tree.
 * Changing a parent resets every child below it.
 */
export default function HierarchyPicker({
    tree,
    value,
    onChange,
    allowEmpty = false,
    errors = {},
}: HierarchyPickerProps) {
    const course = tree.find((c) => String(c.id) === value.course_id);
    const level = course?.levels.find(
        (l) => String(l.id) === value.course_level_id,
    );
    const unit = level?.units.find(
        (u) => String(u.id) === value.course_unit_id,
    );

    const set = (patch: Partial<HierarchySelection>) =>
        onChange({ ...value, ...patch });

    return (
        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label className={labelClass}>Course</label>
                <select
                    className={inputClass}
                    value={value.course_id}
                    onChange={(e) =>
                        set({
                            ...emptySelection,
                            course_id: e.target.value,
                        })
                    }
                >
                    <option value="">
                        {allowEmpty ? 'All courses' : 'Select course'}
                    </option>
                    {tree.map((c) => (
                        <option key={c.id} value={c.id}>
                            {c.name}
                        </option>
                    ))}
                </select>
                {errors.course_id && (
                    <p className="mt-1 text-xs text-red-600">
                        {errors.course_id}
                    </p>
                )}
            </div>

            <div>
                <label className={labelClass}>Level</label>
                <select
                    className={inputClass}
                    value={value.course_level_id}
                    onChange={(e) =>
                        set({
                            course_level_id: e.target.value,
                            course_unit_id: '',
                            course_lesson_id: '',
                        })
                    }
                    disabled={!course}
                >
                    <option value="">
                        {allowEmpty ? 'All levels' : 'Select level'}
                    </option>
                    {course?.levels.map((l) => (
                        <option key={l.id} value={l.id}>
                            {l.name}
                        </option>
                    ))}
                </select>
                {errors.course_level_id && (
                    <p className="mt-1 text-xs text-red-600">
                        {errors.course_level_id}
                    </p>
                )}
            </div>

            <div>
                <label className={labelClass}>Unit</label>
                <select
                    className={inputClass}
                    value={value.course_unit_id}
                    onChange={(e) =>
                        set({
                            course_unit_id: e.target.value,
                            course_lesson_id: '',
                        })
                    }
                    disabled={!level}
                >
                    <option value="">
                        {allowEmpty ? 'All units' : 'Select unit'}
                    </option>
                    {level?.units.map((u) => (
                        <option key={u.id} value={u.id}>
                            {u.name}
                        </option>
                    ))}
                </select>
                {errors.course_unit_id && (
                    <p className="mt-1 text-xs text-red-600">
                        {errors.course_unit_id}
                    </p>
                )}
            </div>

            <div>
                <label className={labelClass}>
                    Lesson{allowEmpty ? '' : ' (optional)'}
                </label>
                <select
                    className={inputClass}
                    value={value.course_lesson_id}
                    onChange={(e) => set({ course_lesson_id: e.target.value })}
                    disabled={!unit}
                >
                    <option value="">
                        {allowEmpty ? 'All lessons' : 'No specific lesson'}
                    </option>
                    {unit?.lessons.map((l) => (
                        <option key={l.id} value={l.id}>
                            {l.name}
                        </option>
                    ))}
                </select>
                {errors.course_lesson_id && (
                    <p className="mt-1 text-xs text-red-600">
                        {errors.course_lesson_id}
                    </p>
                )}
            </div>
        </div>
    );
}
