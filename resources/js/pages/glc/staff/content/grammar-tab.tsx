import {
    QuestionsPanel,
    SECTION_META,
    SectionCard,
    type ContentItem,
} from './shared';

export function GrammarTab({ items }: { items: ContentItem[] }) {
    return (
        <SectionCard
            icon={SECTION_META.grammar_vocabulary.icon}
            title="Grammar & Vocabulary"
            description="Standalone multiple-choice questions — every candidate answers them in this order."
        >
            <QuestionsPanel
                section="grammar_vocabulary"
                parentId={null}
                questions={items}
                heading="Questions"
            />
        </SectionCard>
    );
}
