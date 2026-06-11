import speakingGuidelines from '@/routes/admin/settings/speaking-guidelines';
import GuidelinesEditor, { type GuidelinesPageProps } from './guidelines-editor';

export default function SpeakingGuidelines(props: GuidelinesPageProps) {
    return (
        <GuidelinesEditor
            {...props}
            pageTitle="Speaking Evaluation Guidelines"
            skillLabel="Speaking"
            intro="These criteria are injected verbatim into the AI prompt used to evaluate placement Speaking transcripts. The AI scores each transcribed response against every criterion below, in this order. AI drafts remain staff-only helpers — staff review the recording and transcript before any result is released."
            resetMessage="This discards every customized criterion and restores the default GLC speaking rubric. The change takes effect immediately for new AI evaluations."
            updateUrl={speakingGuidelines.update.url()}
            resetUrl={speakingGuidelines.reset.url()}
        />
    );
}
