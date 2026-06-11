import { Field, inputClass, type Option } from '../components';

export interface UserFormData {
    name: string;
    email: string;
    password: string;
    role: string;
    age: string;
    guardian_name: string;
    guardian_email: string;
    [key: string]: string;
}

export const GUARDIAN_MIN_AGE = 12;
export const GUARDIAN_MAX_AGE = 17;

export function needsGuardian(age: string): boolean {
    const parsed = parseInt(age, 10);

    return (
        !Number.isNaN(parsed) &&
        parsed >= GUARDIAN_MIN_AGE &&
        parsed <= GUARDIAN_MAX_AGE
    );
}

interface UserFieldsProps {
    data: UserFormData;
    setData: (key: keyof UserFormData & string, value: string) => void;
    errors: Partial<Record<keyof UserFormData & string, string>>;
    roles: Option[];
    creating: boolean;
    idPrefix: string;
}

export function UserFields({
    data,
    setData,
    errors,
    roles,
    creating,
    idPrefix,
}: UserFieldsProps) {
    const showGuardian = needsGuardian(data.age);

    return (
        <div className="space-y-4">
            <Field
                label="Name"
                htmlFor={`${idPrefix}-name`}
                error={errors.name}
            >
                <input
                    id={`${idPrefix}-name`}
                    type="text"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    className={inputClass}
                    required
                />
            </Field>

            <Field
                label="Email"
                htmlFor={`${idPrefix}-email`}
                error={errors.email}
            >
                <input
                    id={`${idPrefix}-email`}
                    type="email"
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    className={inputClass}
                    required
                />
            </Field>

            <Field
                label={creating ? 'Password' : 'New password (optional)'}
                htmlFor={`${idPrefix}-password`}
                error={errors.password}
                hint="Minimum 12 characters."
            >
                <input
                    id={`${idPrefix}-password`}
                    type="password"
                    value={data.password}
                    onChange={(e) => setData('password', e.target.value)}
                    className={inputClass}
                    autoComplete="new-password"
                    required={creating}
                />
            </Field>

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <Field
                    label="Role"
                    htmlFor={`${idPrefix}-role`}
                    error={errors.role}
                    hint="Each account has exactly one role."
                >
                    <select
                        id={`${idPrefix}-role`}
                        value={data.role}
                        onChange={(e) => setData('role', e.target.value)}
                        className={inputClass}
                        required
                    >
                        <option value="">Select a role</option>
                        {roles.map((role) => (
                            <option key={role.value} value={role.value}>
                                {role.label}
                            </option>
                        ))}
                    </select>
                </Field>

                <Field
                    label="Age"
                    htmlFor={`${idPrefix}-age`}
                    error={errors.age}
                    hint="Guardian details required for ages 12-17."
                >
                    <input
                        id={`${idPrefix}-age`}
                        type="number"
                        min={5}
                        max={100}
                        value={data.age}
                        onChange={(e) => setData('age', e.target.value)}
                        className={inputClass}
                    />
                </Field>
            </div>

            {showGuardian && (
                <div className="space-y-4 rounded-md border border-amber-200 bg-amber-50 p-4">
                    <p className="text-xs font-medium text-amber-800">
                        This student is aged {GUARDIAN_MIN_AGE}-
                        {GUARDIAN_MAX_AGE}: guardian name and email are
                        required.
                    </p>

                    <Field
                        label="Guardian name"
                        htmlFor={`${idPrefix}-guardian-name`}
                        error={errors.guardian_name}
                    >
                        <input
                            id={`${idPrefix}-guardian-name`}
                            type="text"
                            value={data.guardian_name}
                            onChange={(e) =>
                                setData('guardian_name', e.target.value)
                            }
                            className={inputClass}
                        />
                    </Field>

                    <Field
                        label="Guardian email"
                        htmlFor={`${idPrefix}-guardian-email`}
                        error={errors.guardian_email}
                    >
                        <input
                            id={`${idPrefix}-guardian-email`}
                            type="email"
                            value={data.guardian_email}
                            onChange={(e) =>
                                setData('guardian_email', e.target.value)
                            }
                            className={inputClass}
                        />
                    </Field>
                </div>
            )}
        </div>
    );
}
