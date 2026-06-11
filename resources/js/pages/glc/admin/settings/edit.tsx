import GlcLayout from '@/layouts/glc-layout';
import { Head, useForm } from '@inertiajs/react';
import { type FormEvent } from 'react';
import {
    buttonPrimaryClass,
    Field,
    inputClass,
    type Option,
    StatusBanner,
} from '../components';

interface SettingsEditProps {
    sections: Option[];
    defaults: Record<string, number>;
    effective: Record<string, number>;
    bounds: { min: number; max: number };
    status?: string | null;
}

function minutes(seconds: number): string {
    return (seconds / 60).toFixed(seconds % 60 === 0 ? 0 : 1);
}

export default function SettingsEdit({
    sections,
    defaults,
    effective,
    bounds,
    status,
}: SettingsEditProps) {
    const form = useForm<{
        section_time_limits: Record<string, string>;
    }>({
        section_time_limits: Object.fromEntries(
            sections.map((section) => [
                section.value,
                String(effective[section.value] ?? ''),
            ]),
        ),
    });

    const errors = form.errors as Record<string, string>;

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.put('/admin/settings', { preserveScroll: true });
    };

    return (
        <GlcLayout title="Settings">
            <Head title="Settings" />

            <StatusBanner message={status} />

            <form
                onSubmit={submit}
                className="max-w-xl space-y-4 rounded-lg border border-slate-200 bg-white p-5"
            >
                <div>
                    <h2 className="text-base font-semibold text-slate-900">
                        Placement section time limits
                    </h2>
                    <p className="mt-1 text-sm text-slate-600">
                        Time limit per placement section in seconds, between{' '}
                        {bounds.min} and {bounds.max} ({minutes(bounds.min)} min
                        to {minutes(bounds.max)} min). Values apply to new
                        placement sessions.
                    </p>
                </div>

                {sections.map((section) => (
                    <Field
                        key={section.value}
                        label={section.label}
                        htmlFor={`limit-${section.value}`}
                        error={errors[`section_time_limits.${section.value}`]}
                        hint={`Default: ${defaults[section.value]} seconds (${minutes(defaults[section.value])} min). Current effective: ${effective[section.value]} seconds.`}
                    >
                        <input
                            id={`limit-${section.value}`}
                            type="number"
                            min={bounds.min}
                            max={bounds.max}
                            value={form.data.section_time_limits[section.value]}
                            onChange={(e) =>
                                form.setData('section_time_limits', {
                                    ...form.data.section_time_limits,
                                    [section.value]: e.target.value,
                                })
                            }
                            className={inputClass}
                            required
                        />
                    </Field>
                ))}

                <div className="flex justify-end">
                    <button
                        type="submit"
                        disabled={form.processing}
                        className={buttonPrimaryClass}
                    >
                        Save settings
                    </button>
                </div>
            </form>
        </GlcLayout>
    );
}
