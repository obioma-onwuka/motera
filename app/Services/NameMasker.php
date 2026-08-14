<?php

namespace App\Services;

class NameMasker
{
    /**
     * Mask a person's name for display, keeping only the first letter of
     * the first word and the last letter of the last word.
     * Example: "John Doe" => "J*** **e"
     */
    public static function mask(string $name): string
    {
        $words = preg_split('/\s+/', trim($name)) ?: [];

        if ($words === []) {
            return '';
        }

        $firstWord = array_shift($words);
        $lastWord = array_pop($words) ?? $firstWord;

        $maskedFirst = mb_substr($firstWord, 0, 1).str_repeat('*', max(mb_strlen($firstWord) - 1, 1));
        $maskedLast = str_repeat('*', max(mb_strlen($lastWord) - 2, 1)).mb_substr($lastWord, -1);

        return trim($maskedFirst.' '.$maskedLast);
    }
}
