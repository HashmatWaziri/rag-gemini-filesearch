import writingGuidelines from '@/routes/admin/settings/writing-guidelines';
import GuidelinesEditor, { type GuidelinesPageProps } from './guidelines-editor';

export default function WritingGuidelines(props: GuidelinesPageProps) {
    return (
        <GuidelinesEditor
            {...props}
            pageTitle="Writing Evaluation Guidelines"
            skillLabel="Writing"
            intro="These criteria are injected verbatim into the AI prompt used to evaluate placement Writing tasks. The AI scores each submission against every criterion below, in this order. AI drafts remain staff-only helpers — staff review is always required before any result is released."
            resetMessage="This discards every customized criterion and restores the standard GLC writing rubric. The change takes effect immediately for new AI evaluations."
            updateUrl={writingGuidelines.update.url()}
            resetUrl={writingGuidelines.reset.url()}
        />
    );
}
