import GlcLayout from '@/layouts/glc-layout';
import { Head } from '@inertiajs/react';

export default function TutorBlocked() {
    return (
        <GlcLayout title="AI Tutor">
            <Head title="Guardian consent required" />
            <div className="rounded-xl border border-amber-200 bg-amber-50 p-6 text-center">
                <h2 className="text-lg font-semibold text-amber-900">
                    Guardian consent required
                </h2>
                <p className="mx-auto mt-2 max-w-md text-sm text-amber-800">
                    Because of your age, a guardian must give consent before
                    you can use the AI Tutor. Please ask a GLC admin to confirm
                    your guardian&apos;s consent. Once it is confirmed, the
                    tutor will be available here.
                </p>
            </div>
        </GlcLayout>
    );
}
