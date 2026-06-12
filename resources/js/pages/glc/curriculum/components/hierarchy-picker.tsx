import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    emptySelection,
    type HierarchySelection,
    type TreeCourse,
} from './types';
import { labelClass } from './ui';

const EMPTY = '__empty__';

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

    const courseValue = value.course_id || EMPTY;
    const levelValue = value.course_level_id || EMPTY;
    const unitValue = value.course_unit_id || EMPTY;
    const lessonValue = value.course_lesson_id || EMPTY;

    return (
        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <Label className={labelClass}>Course</Label>
                <Select
                    value={courseValue}
                    onValueChange={(next) =>
                        set({
                            ...emptySelection,
                            course_id: next === EMPTY ? '' : next,
                        })
                    }
                >
                    <SelectTrigger>
                        <SelectValue
                            placeholder={
                                allowEmpty ? 'All courses' : 'Select course'
                            }
                        />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={EMPTY}>
                            {allowEmpty ? 'All courses' : 'Select course'}
                        </SelectItem>
                        {tree.map((c) => (
                            <SelectItem key={c.id} value={String(c.id)}>
                                {c.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                {errors.course_id && (
                    <p className="mt-1 text-xs text-red-600">
                        {errors.course_id}
                    </p>
                )}
            </div>

            <div>
                <Label className={labelClass}>Level</Label>
                <Select
                    value={levelValue}
                    onValueChange={(next) =>
                        set({
                            course_level_id: next === EMPTY ? '' : next,
                            course_unit_id: '',
                            course_lesson_id: '',
                        })
                    }
                    disabled={!course}
                >
                    <SelectTrigger>
                        <SelectValue
                            placeholder={
                                allowEmpty ? 'All levels' : 'Select level'
                            }
                        />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={EMPTY}>
                            {allowEmpty ? 'All levels' : 'Select level'}
                        </SelectItem>
                        {course?.levels.map((l) => (
                            <SelectItem key={l.id} value={String(l.id)}>
                                {l.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                {errors.course_level_id && (
                    <p className="mt-1 text-xs text-red-600">
                        {errors.course_level_id}
                    </p>
                )}
            </div>

            <div>
                <Label className={labelClass}>Unit</Label>
                <Select
                    value={unitValue}
                    onValueChange={(next) =>
                        set({
                            course_unit_id: next === EMPTY ? '' : next,
                            course_lesson_id: '',
                        })
                    }
                    disabled={!level}
                >
                    <SelectTrigger>
                        <SelectValue
                            placeholder={
                                allowEmpty ? 'All units' : 'Select unit'
                            }
                        />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={EMPTY}>
                            {allowEmpty ? 'All units' : 'Select unit'}
                        </SelectItem>
                        {level?.units.map((u) => (
                            <SelectItem key={u.id} value={String(u.id)}>
                                {u.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                {errors.course_unit_id && (
                    <p className="mt-1 text-xs text-red-600">
                        {errors.course_unit_id}
                    </p>
                )}
            </div>

            <div>
                <Label className={labelClass}>
                    Lesson{allowEmpty ? '' : ' (optional)'}
                </Label>
                <Select
                    value={lessonValue}
                    onValueChange={(next) =>
                        set({
                            course_lesson_id: next === EMPTY ? '' : next,
                        })
                    }
                    disabled={!unit}
                >
                    <SelectTrigger>
                        <SelectValue
                            placeholder={
                                allowEmpty
                                    ? 'All lessons'
                                    : 'No specific lesson'
                            }
                        />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={EMPTY}>
                            {allowEmpty
                                ? 'All lessons'
                                : 'No specific lesson'}
                        </SelectItem>
                        {unit?.lessons.map((l) => (
                            <SelectItem key={l.id} value={String(l.id)}>
                                {l.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                {errors.course_lesson_id && (
                    <p className="mt-1 text-xs text-red-600">
                        {errors.course_lesson_id}
                    </p>
                )}
            </div>
        </div>
    );
}
