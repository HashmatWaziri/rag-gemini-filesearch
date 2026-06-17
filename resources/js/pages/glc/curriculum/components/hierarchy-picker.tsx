import { MetronicSelect, mapIdOptions } from '@/components/glc/metronic-select';
import { Label } from '@/components/ui/label';
import {
    emptySelection,
    type HierarchySelection,
    type TreeCourse,
} from './types';
import { labelClass } from './ui';

interface HierarchyPickerProps {
    tree: TreeCourse[];
    value: HierarchySelection;
    onChange: (value: HierarchySelection) => void;
    /** Filter mode: every level may stay empty ("All ..."). */
    allowEmpty?: boolean;
    /** Upload mode: lesson must be chosen (not clearable). */
    requireLesson?: boolean;
    errors?: Partial<Record<keyof HierarchySelection, string>>;
}

function toSelectValue(id: string): string | null {
    return id || null;
}

function fromSelectValue(next: string | null): string {
    return next ?? '';
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
    requireLesson = false,
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

    const courseOptions = mapIdOptions(
        tree,
        allowEmpty ? { value: '', label: 'All courses' } : undefined,
    );
    const levelOptions = mapIdOptions(
        course?.levels ?? [],
        allowEmpty ? { value: '', label: 'All levels' } : undefined,
    );
    const unitOptions = mapIdOptions(
        level?.units ?? [],
        allowEmpty ? { value: '', label: 'All units' } : undefined,
    );
    const lessonOptions = mapIdOptions(
        unit?.lessons ?? [],
        allowEmpty ? { value: '', label: 'All lessons' } : undefined,
    );

    return (
        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <Label className={labelClass}>Course</Label>
                <MetronicSelect
                    value={toSelectValue(value.course_id)}
                    onChange={(next) =>
                        set({
                            ...emptySelection,
                            course_id: fromSelectValue(next),
                        })
                    }
                    options={courseOptions}
                    placeholder={
                        allowEmpty ? 'All courses' : 'Select course'
                    }
                    isSearchable={false}
                />
                {errors.course_id && (
                    <p className="mt-1 text-xs text-red-600">
                        {errors.course_id}
                    </p>
                )}
            </div>

            <div>
                <Label className={labelClass}>Level</Label>
                <MetronicSelect
                    value={toSelectValue(value.course_level_id)}
                    onChange={(next) =>
                        set({
                            course_level_id: fromSelectValue(next),
                            course_unit_id: '',
                            course_lesson_id: '',
                        })
                    }
                    options={levelOptions}
                    placeholder={allowEmpty ? 'All levels' : 'Select level'}
                    disabled={!course}
                    isSearchable={false}
                />
                {errors.course_level_id && (
                    <p className="mt-1 text-xs text-red-600">
                        {errors.course_level_id}
                    </p>
                )}
            </div>

            <div>
                <Label className={labelClass}>Unit</Label>
                <MetronicSelect
                    value={toSelectValue(value.course_unit_id)}
                    onChange={(next) =>
                        set({
                            course_unit_id: fromSelectValue(next),
                            course_lesson_id: '',
                        })
                    }
                    options={unitOptions}
                    placeholder={allowEmpty ? 'All units' : 'Select unit'}
                    disabled={!level}
                    isSearchable={false}
                />
                {errors.course_unit_id && (
                    <p className="mt-1 text-xs text-red-600">
                        {errors.course_unit_id}
                    </p>
                )}
            </div>

            <div>
                <Label className={labelClass}>
                    Lesson
                    {allowEmpty
                        ? ''
                        : requireLesson
                          ? ' (required)'
                          : ' (optional)'}
                </Label>
                <MetronicSelect
                    value={toSelectValue(value.course_lesson_id)}
                    onChange={(next) =>
                        set({
                            course_lesson_id: fromSelectValue(next),
                        })
                    }
                    options={lessonOptions}
                    placeholder={
                        allowEmpty
                            ? 'All lessons'
                            : requireLesson
                              ? 'Select lesson'
                              : 'No specific lesson'
                    }
                    disabled={!unit}
                    isClearable={!allowEmpty && !requireLesson}
                    isSearchable={false}
                />
                {errors.course_lesson_id && (
                    <p className="mt-1 text-xs text-red-600">
                        {errors.course_lesson_id}
                    </p>
                )}
            </div>
        </div>
    );
}
