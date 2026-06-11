<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Glc\PlacementItemType;
use App\Enums\Glc\PlacementSection;
use App\Models\Glc\PlacementItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

final class GlcPlacementContentSeeder extends Seeder
{
    public function run(): void
    {
        if (PlacementItem::query()->active()->exists()) {
            return;
        }

        $this->seedReading();
        $this->seedGrammarVocabulary();
        $this->seedListening();
        $this->seedPrompts();
    }

    private function seedReading(): void
    {
        foreach ($this->readingPassages() as $position => $passage) {
            $parent = PlacementItem::query()->create([
                'section' => PlacementSection::Reading,
                'type' => PlacementItemType::Passage,
                'position' => $position + 1,
                'title' => $passage['title'],
                'body' => $passage['body'],
                'is_active' => true,
            ]);

            $this->createQuestions($parent, PlacementSection::Reading, $passage['questions']);
        }
    }

    private function seedGrammarVocabulary(): void
    {
        foreach ($this->grammarVocabularyQuestions() as $position => $question) {
            PlacementItem::query()->create([
                'section' => PlacementSection::GrammarVocabulary,
                'type' => PlacementItemType::Question,
                'position' => $position + 1,
                'body' => $question[0],
                'options' => $question[1],
                'correct_option' => $question[2],
                'is_active' => true,
            ]);
        }
    }

    private function seedListening(): void
    {
        foreach ($this->listeningClips() as $position => $clip) {
            $path = sprintf('glc/placement/audio/placeholder-clip-%d.wav', $position + 1);
            Storage::disk('local')->put($path, $this->silentWav());

            $parent = PlacementItem::query()->create([
                'section' => PlacementSection::Listening,
                'type' => PlacementItemType::AudioClip,
                'position' => $position + 1,
                'title' => $clip['title'],
                'media_path' => $path,
                'is_active' => true,
            ]);

            $this->createQuestions($parent, PlacementSection::Listening, $clip['questions']);
        }
    }

    private function seedPrompts(): void
    {
        PlacementItem::query()->create([
            'section' => PlacementSection::Writing,
            'type' => PlacementItemType::Prompt,
            'position' => 1,
            'title' => 'Essay',
            'body' => 'Some people prefer to study English in a classroom, while others prefer to learn online. '
                .'Which do you prefer, and why? Give reasons and examples from your own experience.',
            'settings' => [
                'min_words' => config()->integer('glc.placement.writing.min_words', 150),
                'max_words' => config()->integer('glc.placement.writing.max_words', 250),
            ],
            'is_active' => true,
        ]);

        PlacementItem::query()->create([
            'section' => PlacementSection::Speaking,
            'type' => PlacementItemType::Prompt,
            'position' => 1,
            'title' => 'Speaking response',
            'body' => 'Describe a place in your city that you enjoy visiting. Explain where it is, what you do there, '
                .'and why you would recommend it to a visitor. Speak for up to three minutes.',
            'settings' => [
                'max_duration_seconds' => config()->integer('glc.placement.speaking.max_duration_seconds', 180),
                'max_attempts' => config()->integer('glc.placement.speaking.max_attempts', 3),
            ],
            'is_active' => true,
        ]);
    }

    /**
     * @param  list<array{0: string, 1: list<string>, 2: int}>  $questions
     */
    private function createQuestions(PlacementItem $parent, PlacementSection $section, array $questions): void
    {
        foreach ($questions as $position => $question) {
            PlacementItem::query()->create([
                'section' => $section,
                'type' => PlacementItemType::Question,
                'parent_id' => $parent->id,
                'position' => $position + 1,
                'body' => $question[0],
                'options' => $question[1],
                'correct_option' => $question[2],
                'is_active' => true,
            ]);
        }
    }

    private function silentWav(): string
    {
        $sampleRate = 8000;
        $data = str_repeat(chr(0x80), 4000);

        return 'RIFF'.pack('V', 36 + mb_strlen($data)).'WAVE'
            .'fmt '.pack('V', 16).pack('v', 1).pack('v', 1)
            .pack('V', $sampleRate).pack('V', $sampleRate).pack('v', 1).pack('v', 8)
            .'data'.pack('V', mb_strlen($data)).$data;
    }

    /**
     * @return list<array{title: string, body: string, questions: list<array{0: string, 1: list<string>, 2: int}>}>
     */
    private function readingPassages(): array
    {
        return [
            [
                'title' => 'The Night Market',
                'body' => 'Every Friday evening, the streets near Amir\'s home fill with the smells of grilled corn, fried '
                    .'noodles, and sweet pancakes. The night market has been part of the neighbourhood for more than '
                    .'thirty years. Traders arrive in the late afternoon to set up their stalls, hanging bright lights '
                    .'and arranging their goods carefully. Some sell food, while others offer clothes, toys, or fresh '
                    .'fruit and vegetables.'."\n\n"
                    .'Amir\'s favourite stall belongs to Mrs. Tan, who has sold homemade snacks at the market since he '
                    .'was a small boy. She knows most of her customers by name and often saves the last box of coconut '
                    .'cakes for Amir\'s grandmother. "The market is not just about shopping," Mrs. Tan says. "It is '
                    .'where neighbours meet, share news, and feel part of something."'."\n\n"
                    .'Recently, the city council suggested moving the market to a new indoor centre with air '
                    .'conditioning and modern facilities. Some traders welcome the idea because rain often interrupts '
                    .'business. Others worry that the special atmosphere of the street market will be lost, and that '
                    .'higher rents will force smaller stalls to close.',
                'questions' => [
                    ['How often does the night market take place?', ['Every evening', 'Once a week', 'Once a month', 'Twice a week'], 1],
                    ['What does Mrs. Tan sell at the market?', ['Clothes and toys', 'Fresh vegetables', 'Homemade snacks', 'Grilled corn'], 2],
                    ['Why does Mrs. Tan think the market is important?', ['It brings the community together', 'It is the cheapest place to shop', 'It stays open late at night', 'It attracts many tourists'], 0],
                    ['Why do some traders support moving indoors?', ['The rent would be lower', 'Rain often interrupts business', 'The new centre is closer to town', 'They want larger stalls'], 1],
                    ['What worries some traders about the new indoor centre?', ['It will open on different days', 'There will be no parking', 'Higher rents may close small stalls', 'Customers prefer shopping online'], 2],
                    ['Who does Mrs. Tan save coconut cakes for?', ['Amir', 'Amir\'s grandmother', 'The city council', 'Her own family'], 1],
                ],
            ],
            [
                'title' => 'Learning to Sail',
                'body' => 'When Layla turned sixteen, her uncle offered to teach her how to sail his small boat. At first '
                    .'she refused. She had never been confident in the water, and the harbour looked enormous from the '
                    .'shore. Her uncle did not argue. Instead, he invited her simply to sit in the boat while it was '
                    .'tied to the dock, learning the names of the ropes and sails.'."\n\n"
                    .'Within a month, Layla found herself asking questions about wind direction and how the sail '
                    .'catches moving air. Her uncle answered patiently, never pushing her further than she wanted to '
                    .'go. By the end of the summer, Layla could steer the boat across the harbour on her own, reading '
                    .'the water for changes in the wind.'."\n\n"
                    .'Years later, Layla became a sailing instructor herself. She often tells her nervous students the '
                    .'same thing her uncle told her: "Nobody learns to sail by being thrown into the sea. You learn '
                    .'one rope at a time." Her courses are popular because she lets each student move at their own '
                    .'speed, building confidence before skill.',
                'questions' => [
                    ['Why did Layla first refuse to learn sailing?', ['She disliked her uncle', 'She was not confident in the water', 'The boat was too small', 'She was too busy studying'], 1],
                    ['What did her uncle do after she refused?', ['He sold the boat', 'He asked her parents for help', 'He let her sit in the docked boat', 'He taught her brother instead'], 2],
                    ['What could Layla do by the end of the summer?', ['Repair the sails', 'Teach other students', 'Swim across the harbour', 'Steer the boat on her own'], 3],
                    ['What did Layla become later in life?', ['A sailing instructor', 'A harbour master', 'A boat builder', 'A swimming coach'], 0],
                    ['What is the main message of her uncle\'s advice?', ['Learning should happen step by step', 'Sailing requires expensive equipment', 'Fear cannot be overcome', 'The sea is dangerous'], 0],
                    ['Why are Layla\'s courses popular?', ['They are the cheapest in town', 'Students progress at their own speed', 'They include free equipment', 'They are held in summer only'], 1],
                ],
            ],
        ];
    }

    /**
     * @return list<array{0: string, 1: list<string>, 2: int}>
     */
    private function grammarVocabularyQuestions(): array
    {
        return [
            ['She ____ to the gym every morning before work.', ['go', 'goes', 'going', 'gone'], 1],
            ['I have lived in Kuala Lumpur ____ 2019.', ['for', 'since', 'during', 'from'], 1],
            ['If it rains tomorrow, we ____ the picnic.', ['cancel', 'cancelled', 'will cancel', 'would cancel'], 2],
            ['Choose the sentence with an error: ', ['He doesn\'t like coffee.', 'They was late for class.', 'We have finished our homework.', 'She is taller than her brother.'], 1],
            ['The meeting was ____ because the manager was ill.', ['put off', 'put on', 'put up', 'put in'], 0],
            ['By the time we arrived, the film ____.', ['already started', 'has already started', 'had already started', 'was already starting'], 2],
            ['Please speak more ____; I cannot hear you.', ['loud', 'loudly', 'louder than', 'loudness'], 1],
            ['Correct this sentence: "He suggested to go home early."', ['He suggested going home early.', 'He suggested go home early.', 'He suggested went home early.', 'He suggested for going home early.'], 0],
            ['My sister is very ____ in history and old buildings.', ['interesting', 'interest', 'interested', 'interests'], 2],
            ['You ____ smoke in the hospital. It is forbidden.', ['don\'t have to', 'mustn\'t', 'shouldn\'t have', 'might not'], 1],
            ['The opposite of "generous" is ____.', ['kind', 'wealthy', 'mean', 'careful'], 2],
            ['Neither the students nor the teacher ____ the answer.', ['know', 'knows', 'knowing', 'have known'], 1],
            ['I look forward to ____ from you soon.', ['hear', 'heard', 'hearing', 'be hearing'], 2],
            ['Choose the sentence with an error:', ['She has been working here for ten years.', 'The children is playing outside.', 'I would rather stay at home.', 'It might rain later today.'], 1],
            ['This is the restaurant ____ we celebrated my birthday.', ['which', 'who', 'where', 'whom'], 2],
            ['He apologised ____ arriving late to the interview.', ['for', 'about', 'of', 'to'], 0],
            ['The new policy will ____ all employees, not just managers.', ['effect', 'affect', 'afford', 'offend'], 1],
            ['____ being tired, she finished the report before midnight.', ['Although', 'Because', 'Despite', 'However'], 2],
            ['Correct this sentence: "There is less people here today."', ['There is fewer people here today.', 'There are less people here today.', 'There are fewer people here today.', 'There be fewer people here today.'], 2],
            ['A person who designs buildings is called an ____.', ['engineer', 'architect', 'electrician', 'estate agent'], 1],
            ['The students were told ____ their phones during the exam.', ['not using', 'don\'t use', 'not to use', 'to not be used'], 2],
            ['Hardly ____ the house when it started to rain.', ['we had left', 'had we left', 'we left', 'did we left'], 1],
        ];
    }

    /**
     * @return list<array{title: string, questions: list<array{0: string, 1: list<string>, 2: int}>}>
     */
    private function listeningClips(): array
    {
        return [
            [
                'title' => 'Conversation: Booking a language course (placeholder audio)',
                'questions' => [
                    ['What does the caller want to do?', ['Cancel a class', 'Book a language course', 'Complain about a teacher', 'Change his address'], 1],
                    ['Which level does the receptionist recommend?', ['Beginner', 'Elementary', 'Intermediate', 'Advanced'], 2],
                    ['When do the evening classes start?', ['At 6 pm', 'At 6.30 pm', 'At 7 pm', 'At 7.30 pm'], 2],
                    ['How many students are in each group?', ['Up to eight', 'Up to ten', 'Up to twelve', 'Up to fifteen'], 2],
                    ['What should the caller bring on the first day?', ['His passport', 'A notebook and ID', 'The full course fee', 'A placement certificate'], 1],
                ],
            ],
            [
                'title' => 'Announcement: Library opening hours (placeholder audio)',
                'questions' => [
                    ['What is the announcement mainly about?', ['New library opening hours', 'A book sale', 'Building repairs', 'A reading competition'], 0],
                    ['On which day will the library be closed?', ['Friday', 'Saturday', 'Sunday', 'Monday'], 2],
                    ['Until what time is the library open on weekdays?', ['8 pm', '9 pm', '10 pm', '6 pm'], 1],
                    ['Where should visitors return borrowed books?', ['The front desk', 'The book drop box', 'The second floor', 'By post'], 1],
                    ['Who can use the new study rooms?', ['Staff only', 'Children only', 'Registered members', 'University students'], 2],
                ],
            ],
        ];
    }
}
