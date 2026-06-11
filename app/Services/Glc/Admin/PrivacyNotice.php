<?php

declare(strict_types=1);

namespace App\Services\Glc\Admin;

final class PrivacyNotice
{
    public static function text(): string
    {
        return <<<'TEXT'
Greats Language Center (GLC) collects the personal data on this form (name, email, age and, for students aged 12-17, guardian name and email) to provide language placement and tutoring services. Data is processed in line with the Personal Data Protection Act 2010 (PDPA) of Malaysia, is hosted in a Southeast Asia region, and is never used to train AI models. For students aged 12-17, guardian consent must be confirmed by GLC before the AI Tutor can be used or placement results are sent. You may request access, correction, deletion or anonymization of personal data by contacting GLC. [Placeholder notice - final PDPA text to be supplied by GLC.]
TEXT;
    }
}
