<?php

namespace App\Domain\Common;

use Transliterator;

class Slugger
{
    public static function generate(string $text): string
    {
        // 1. Custom replacements for specific symbols (Preferences)
        $text = str_replace(
            ['&', '@', '€', '$'],
            [' and ', ' at ', ' euro ', ' dollar '],
            $text
        );

        // 2. Transliterate: specific logic to turn UTF-8 into ASCII
        // 'Any-Latin; Latin-ASCII' converts characters like 'cioè' -> 'cioe', 'Å' -> 'A'
        $transliterator = Transliterator::create('Any-Latin; Latin-ASCII');
        if ($transliterator) {
            $text = $transliterator->transliterate($text);
        }

        // 3. Lowercase
        $text = strtolower($text);

        // 4. Regex: Remove anything that isn't a letter, number, or whitespace
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text);

        // 5. Replace multiple spaces or hyphens with a single hyphen
        $text = preg_replace('/[\s-]+/', '-', $text);

        // 6. Trim hyphens from start/end
        return trim($text, '-');
    }
}
