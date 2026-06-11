import GlcLayout from '@/layouts/glc-layout';
import { Head } from '@inertiajs/react';

export default function TutorBlocked() {
    return (
        <GlcLayout title="AI Tutor">
            <Head title="Guardian permission needed" />
            <div className="rounded-xl border border-amber-200 bg-amber-50 p-6 text-center">
                <h2 className="text-lg font-semibold text-amber-900">
                    We need your guardian&apos;s permission first
                </h2>
                <p className="mx-auto mt-2 max-w-md text-sm text-amber-800">
                    Because you are under 18, your parent or guardian needs to
                    give GLC their permission before you can use the tutor.
                    Please ask your teacher or the GLC office to arrange it —
                    once that is done, the tutor will be ready for you here.
                </p>
            </div>
        </GlcLayout>
    );
}
