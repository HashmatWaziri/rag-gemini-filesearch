import {
    type ClipboardEvent,
    useEffect,
    useRef,
    useState,
} from 'react';
import FinishSection from '../components/finish-section';
import SectionShell from '../components/section-shell';
import { placementApi } from '../lib/placement-api';
import { type SaveState, type SectionPageProps } from '../lib/types';

interface WritingProps extends SectionPageProps {
    prompt: {
        id: number;
        title: string | null;
        body: string | null;
        minWords: number;
        maxWords: number;
    } | null;
    saved: { text: string; wordCount: number };
}

function countWords(text: string): number {
    const words = text.trim().split(/\s+/u);

    return text.trim() === '' ? 0 : words.length;
}

export default function Writing({
    progress,
    timer,
    config,
    prompt,
    saved,
}: WritingProps) {
    const [text, setText] = useState(saved.text);
    const [saveState, setSaveState] = useState<SaveState>('idle');
    const [pasteWarning, setPasteWarning] = useState(false);
    const dirtyRef = useRef(false);
    const textRef = useRef(saved.text);

    const persist = async () => {
        if (!dirtyRef.current) {
            return;
        }

        dirtyRef.current = false;
        setSaveState('saving');
        const result = await placementApi.saveWriting(textRef.current);
        setSaveState(result.ok ? 'saved' : 'error');
    };

    // Autosave every few seconds (configured) while there are unsaved
    // changes; a brief disconnect therefore loses at most one interval.
    useEffect(() => {
        const interval = window.setInterval(
            () => void persist(),
            config.autosaveIntervalSeconds * 1000,
        );

        return () => window.clearInterval(interval);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [config.autosaveIntervalSeconds]);

    const onChange = (value: string) => {
        setText(value);
        textRef.current = value;
        dirtyRef.current = true;
    };

    // Paste is blocked and every attempt is recorded as an integrity event.
    const onPaste = (event: ClipboardEvent<HTMLTextAreaElement>) => {
        event.preventDefault();
        setPasteWarning(true);
        void placementApi.reportIntegrity('paste_attempt', 'writing_textarea');
    };

    const wordCount = countWords(text);
    const minWords = prompt?.minWords ?? 150;
    const maxWords = prompt?.maxWords ?? 250;
    const belowMin = wordCount < minWords;
    const aboveMax = wordCount > maxWords;

    return (
        <SectionShell
            progress={progress}
            timer={timer}
            config={config}
            saveState={saveState}
        >
            {prompt && (
                <div className="rounded-lg border border-slate-200 bg-white p-4">
                    {prompt.title && (
                        <h2 className="mb-1 font-semibold">{prompt.title}</h2>
                    )}
                    <p className="text-sm leading-relaxed text-slate-700">
                        {prompt.body}
                    </p>
                    <p className="mt-2 text-xs text-slate-500">
                        Write between {minWords} and {maxWords} words.
                    </p>
                </div>
            )}

            {pasteWarning && (
                <div
                    className="mt-3 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-800"
                    role="alert"
                >
                    Pasting is not allowed in the writing area. This attempt
                    has been recorded.
                </div>
            )}

            <div className="mt-3">
                <textarea
                    value={text}
                    onChange={(event) => onChange(event.target.value)}
                    onPaste={onPaste}
                    onBlur={() => void persist()}
                    rows={14}
                    className="w-full rounded-lg border border-slate-300 p-3 text-base leading-relaxed focus:border-emerald-500 focus:ring-emerald-500"
                    placeholder="Write your essay here..."
                    aria-label="Essay"
                />
                <div className="mt-1 flex items-center justify-between text-sm">
                    <span
                        className={
                            belowMin
                                ? 'text-slate-500'
                                : aboveMax
                                  ? 'text-amber-600'
                                  : 'text-emerald-600'
                        }
                    >
                        {wordCount} {wordCount === 1 ? 'word' : 'words'}
                    </span>
                    {belowMin && (
                        <span className="text-slate-500">
                            At least {minWords} words required
                        </span>
                    )}
                    {aboveMax && (
                        <span className="text-amber-600">
                            {`Above ${maxWords} words - consider shortening (you can still submit)`}
                        </span>
                    )}
                </div>
            </div>

            <FinishSection
                section={progress.current}
                nextLabel="Speaking"
                disabled={belowMin}
                disabledReason={`You need at least ${minWords} words to finish this section.`}
            />
        </SectionShell>
    );
}
