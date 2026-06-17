import { Badge } from '@/components/ui/badge';
import GlcLayout from '@/layouts/glc-layout';
import { cn } from '@/lib/utils';
import { Head, usePage } from '@inertiajs/react';
import { CircleCheck, TriangleAlert } from 'lucide-react';
import { useState } from 'react';
import { type CriteriaData } from './content/criteria-panel';
import { GrammarTab } from './content/grammar-tab';
import { ListeningTab } from './content/listening-tab';
import { PdfImportTab } from './content/pdf-import-tab';
import { SpeakingTab, WritingTab } from './content/prompts-tab';
import { ReadingTab } from './content/reading-tab';
import {
    SECTION_KEYS,
    SECTION_META,
    type ContentSections,
    type TabKey,
} from './content/shared';

interface PageProps {
    sections: ContentSections;
    criteria: { writing: CriteriaData; speaking: CriteriaData };
    [key: string]: unknown;
}

const ALL_TABS: TabKey[] = [...SECTION_KEYS, 'pdf'];

const plural = (count: number, word: string): string =>
    `${count} ${word}${count === 1 ? '' : 's'}`;

interface SectionFact {
    key: TabKey;
    value: string;
    ready: boolean;
    readyLabel: string;
}

/**
 * Per-section health summary for the overview strip and the sidebar nav.
 * "Ready" means the section will actually show something to candidates.
 */
function buildFacts(sections: ContentSections): SectionFact[] {
    const passages = sections.reading.filter((i) => i.type === 'passage');
    const readingQuestions = passages.reduce(
        (total, passage) => total + passage.children.length,
        0,
    );
    const clips = sections.listening.filter((i) => i.type === 'audio_clip');
    const listeningQuestions = clips.reduce(
        (total, clip) => total + clip.children.length,
        0,
    );
    const writingPrompt = sections.writing.some((i) => i.type === 'prompt');
    const speakingPrompt = sections.speaking.some((i) => i.type === 'prompt');

    return [
        {
            key: 'reading',
            value: `${plural(passages.length, 'passage')} · ${plural(readingQuestions, 'question')}`,
            ready: passages.length > 0 && readingQuestions > 0,
            readyLabel:
                passages.length === 0
                    ? 'No passages yet'
                    : readingQuestions === 0
                      ? 'Passages have no questions'
                      : 'Ready',
        },
        {
            key: 'grammar_vocabulary',
            value: plural(sections.grammar_vocabulary.length, 'question'),
            ready: sections.grammar_vocabulary.length > 0,
            readyLabel:
                sections.grammar_vocabulary.length > 0
                    ? 'Ready'
                    : 'No questions yet',
        },
        {
            key: 'listening',
            value: `${plural(clips.length, 'clip')} · ${plural(listeningQuestions, 'question')}`,
            ready: clips.length > 0 && listeningQuestions > 0,
            readyLabel:
                clips.length === 0
                    ? 'No clips yet'
                    : listeningQuestions === 0
                      ? 'Clips have no questions'
                      : 'Ready',
        },
        {
            key: 'writing',
            value: writingPrompt ? 'Prompt set' : 'No prompt',
            ready: writingPrompt,
            readyLabel: writingPrompt ? 'Ready' : 'No prompt yet',
        },
        {
            key: 'speaking',
            value: speakingPrompt ? 'Prompt set' : 'No prompt',
            ready: speakingPrompt,
            readyLabel: speakingPrompt ? 'Ready' : 'No prompt yet',
        },
    ];
}

function initialTab(): TabKey {
    if (typeof window === 'undefined') {
        return 'reading';
    }

    const hash = window.location.hash.replace('#', '');

    return (ALL_TABS as string[]).includes(hash) ? (hash as TabKey) : 'reading';
}

/**
 * Test-form overview strip (Metronic facts-strip pattern): one column per
 * section in the fixed candidate order, with a readiness check and dashed
 * connectors echoing the placement process stepper.
 */
function FactsStrip({
    facts,
    activeTab,
    onSelect,
}: {
    facts: SectionFact[];
    activeTab: TabKey;
    onSelect: (tab: TabKey) => void;
}) {
    return (
        <section
            aria-label="Test form overview"
            className="rounded-xl border border-border bg-muted/40 px-5 py-4 shadow-xs"
        >
            <ol className="flex flex-wrap items-center gap-x-3 gap-y-2 lg:gap-x-2">
                {facts.map((fact, index) => (
                    <li
                        key={fact.key}
                        className="flex items-center gap-3 lg:gap-2"
                    >
                        {index > 0 && (
                            <span
                                aria-hidden
                                className="hidden h-px w-5 border-t border-dashed border-zinc-300 lg:block xl:w-8 dark:border-zinc-600"
                            />
                        )}
                        <button
                            type="button"
                            onClick={() => onSelect(fact.key)}
                            title={fact.readyLabel}
                            className="flex flex-col gap-1 rounded-lg px-2 py-1 text-start transition-colors hover:bg-accent/60 focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                        >
                            <span
                                className={cn(
                                    'text-2xs font-semibold tracking-wide uppercase',
                                    activeTab === fact.key
                                        ? 'text-primary'
                                        : 'text-secondary-foreground',
                                )}
                            >
                                {index + 1} · {SECTION_META[fact.key].label}
                            </span>
                            <span className="flex items-center gap-1.5 text-sm font-medium text-mono">
                                {fact.value}
                                {fact.ready ? (
                                    <CircleCheck
                                        aria-label="Section ready"
                                        className="size-3.5 text-green-500"
                                    />
                                ) : (
                                    <TriangleAlert
                                        aria-label={fact.readyLabel}
                                        className="size-3.5 text-amber-500"
                                    />
                                )}
                            </span>
                        </button>
                    </li>
                ))}
            </ol>
        </section>
    );
}

function NavItem({
    tab,
    active,
    count,
    dot,
    onSelect,
}: {
    tab: TabKey;
    active: boolean;
    count?: number;
    dot?: 'ready' | 'missing';
    onSelect: (tab: TabKey) => void;
}) {
    const meta = SECTION_META[tab];
    const Icon = meta.icon;

    return (
        <li className="shrink-0 lg:shrink">
            <button
                type="button"
                onClick={() => onSelect(tab)}
                aria-current={active ? 'page' : undefined}
                className={cn(
                    'flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm whitespace-nowrap transition-colors focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none',
                    active
                        ? 'bg-accent font-medium text-primary'
                        : 'text-secondary-foreground hover:bg-accent/50 hover:text-foreground',
                )}
            >
                <Icon
                    aria-hidden
                    className={cn(
                        'size-4 shrink-0',
                        active ? 'text-primary' : 'text-muted-foreground',
                    )}
                />
                {meta.label}
                {typeof count === 'number' && (
                    <Badge
                        variant="outline"
                        className="ms-auto text-2xs max-lg:hidden"
                    >
                        {count}
                    </Badge>
                )}
                {dot && (
                    <span
                        aria-hidden
                        className={cn(
                            'ms-auto size-1.5 rounded-full max-lg:hidden',
                            dot === 'ready' ? 'bg-green-500' : 'bg-amber-400',
                        )}
                    />
                )}
            </button>
        </li>
    );
}

export default function ContentIndex() {
    const { sections, criteria } = usePage<PageProps>().props;
    const [tab, setTab] = useState<TabKey>(initialTab);

    const facts = buildFacts(sections);

    const selectTab = (next: TabKey) => {
        setTab(next);
        window.history.replaceState(null, '', `#${next}`);
    };

    const counts: Partial<Record<TabKey, number>> = {
        reading: sections.reading.filter((i) => i.type === 'passage').length,
        grammar_vocabulary: sections.grammar_vocabulary.length,
        listening: sections.listening.filter((i) => i.type === 'audio_clip')
            .length,
    };

    const dots: Partial<Record<TabKey, 'ready' | 'missing'>> = {
        writing: facts[3].ready ? 'ready' : 'missing',
        speaking: facts[4].ready ? 'ready' : 'missing',
    };

    const writingPrompt = sections.writing.find((i) => i.type === 'prompt');
    const speakingPrompt = sections.speaking.find((i) => i.type === 'prompt');

    return (
        <GlcLayout title="Placement Test Content">
            <Head title="Placement Test Content" />

            <div className="space-y-5">
                <p className="text-sm text-secondary-foreground">
                    One fixed test form — every candidate sees the same items in
                    the same order. Only active items appear in the test.
                </p>

                <FactsStrip
                    facts={facts}
                    activeTab={tab}
                    onSelect={selectTab}
                />

                <div className="flex flex-col gap-5 lg:flex-row lg:gap-7.5">
                    <aside className="w-full shrink-0 lg:w-[240px]">
                        <nav
                            aria-label="Placement content sections"
                            className="rounded-xl border border-border bg-card p-2.5 lg:sticky lg:top-[calc(var(--header-height)+1.25rem)]"
                        >
                            <p className="hidden px-2.5 pt-1 pb-1.5 text-2xs font-semibold tracking-wide text-muted-foreground uppercase lg:block">
                                Test sections · fixed order
                            </p>
                            <ul className="flex gap-1 overflow-x-auto pb-1 lg:flex-col lg:overflow-visible lg:pb-0">
                                {SECTION_KEYS.map((key) => (
                                    <NavItem
                                        key={key}
                                        tab={key}
                                        active={tab === key}
                                        count={counts[key]}
                                        dot={dots[key]}
                                        onSelect={selectTab}
                                    />
                                ))}
                                <li
                                    aria-hidden
                                    className="my-2 hidden border-t border-border lg:block"
                                />
                                <li className="hidden px-2.5 pb-1.5 text-2xs font-semibold tracking-wide text-muted-foreground uppercase lg:block">
                                    Tools
                                </li>
                                <NavItem
                                    tab="pdf"
                                    active={tab === 'pdf'}
                                    onSelect={selectTab}
                                />
                            </ul>
                        </nav>
                    </aside>

                    <div className="min-w-0 grow">
                        {tab === 'reading' && (
                            <ReadingTab items={sections.reading} />
                        )}
                        {tab === 'grammar_vocabulary' && (
                            <GrammarTab items={sections.grammar_vocabulary} />
                        )}
                        {tab === 'listening' && (
                            <ListeningTab items={sections.listening} />
                        )}
                        {tab === 'writing' && (
                            <WritingTab
                                prompt={writingPrompt}
                                criteria={criteria.writing}
                            />
                        )}
                        {tab === 'speaking' && (
                            <SpeakingTab
                                prompt={speakingPrompt}
                                criteria={criteria.speaking}
                            />
                        )}
                        {tab === 'pdf' && <PdfImportTab />}
                    </div>
                </div>
            </div>
        </GlcLayout>
    );
}
