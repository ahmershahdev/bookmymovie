<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * Name, username and profanity rules shared by sign-up, social sign-in,
 * profile edits and reviews, so every entry point enforces the same thing.
 *
 * Profanity matching normalises first (lowercase, accents stripped, leetspeak
 * such as 5h1t or @ss mapped back, separators removed, repeated letters
 * collapsed) so "f.u_u_c-k", "fuuuck" and "phuck" style dodges are caught.
 * Words long enough to be unambiguous match anywhere inside the text; short
 * ones (ass, cum, tit, ...) only match as whole words so "classic",
 * "cucumber" and "title" stay usable.
 */
class CleanText
{
    /** Match anywhere, even inside other words. English and Roman Urdu. */
    private const BLOCKED_ANYWHERE = [
        'fuck', 'phuck', 'shit', 'bitch', 'biatch', 'cunt', 'pussy', 'asshole', 'arsehole', 'bastard', 'slut', 'whore',
        'nigger', 'nigga', 'faggot', 'porn', 'hitler', 'penis', 'vagina', 'boobs', 'wank', 'twat', 'retard', 'dildo',
        'blowjob', 'handjob', 'motherfucker', 'jerkoff', 'hentai', 'onlyfans', 'incest', 'rapist', 'killyourself',
        'chutiya', 'chootiya', 'chutia', 'bhenchod', 'behenchod', 'benchod', 'bhanchod', 'madarchod', 'maderchod', 'madarjat',
        'gandu', 'gaandu', 'haramzada', 'haramzadi', 'bhosdi', 'bhosda', 'lauda', 'lawda', 'kanjar', 'kanjri', 'gashti',
    ];

    /**
     * Only as a whole word (split on spaces, digits, underscores and
     * punctuation): short or ambiguous words that live inside innocent ones
     * (classic, grape, torpedo, Nazia, Abdallah, Hancock, Chaudhry).
     */
    private const BLOCKED_WORDS = [
        'ass', 'arse', 'cum', 'tit', 'tits', 'fag', 'sex', 'sexy', 'hoe', 'hoes', 'jizz', 'wtf', 'stfu', 'fuk', 'fck', 'dick', 'cock',
        'rape', 'pedo', 'nazi', 'horny', 'milf', 'xxx', 'kys', 'lund', 'randi', 'dalla', 'suar', 'chod', 'kamina', 'kameena',
        'kutta', 'kutti', 'kuttay', 'harami', 'khusra', 'tatte', 'ullu', 'lora',
    ];

    private const LEET = ['0' => 'o', '1' => 'i', '!' => 'i', '|' => 'i', '3' => 'e', '4' => 'a', '@' => 'a', '5' => 's', '$' => 's', '7' => 't', '8' => 'b', '9' => 'g', '+' => 't'];

    public static function isProfane(?string $text): bool
    {
        $text = Str::of((string) $text)->ascii()->lower()->value();

        if ($text === '') {
            return false;
        }

        $words = preg_split('/[^a-z]+/', strtr($text, self::LEET), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        foreach ($words as $word) {
            if (in_array($word, self::BLOCKED_WORDS, true) || in_array(self::squeeze($word), self::BLOCKED_WORDS, true)) {
                return true;
            }
        }

        // Everything joined, both as written and with repeats collapsed.
        $joined = preg_replace('/[^a-z]+/', '', strtr($text, self::LEET)) ?? '';
        $squeezed = self::squeeze($joined);
        $joined = str_replace('ph', 'f', $joined);

        foreach (self::BLOCKED_ANYWHERE as $word) {
            if (str_contains($joined, $word) || str_contains($squeezed, self::squeeze($word))) {
                return true;
            }
        }

        return false;
    }

    /** Validation closure: rejects profanity with a neutral message. */
    public static function noProfanity(string $what = 'This'): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($what): void {
            if (self::isProfane((string) $value)) {
                $fail($what.' contains language we do not allow. Please rephrase it.');
            }
        };
    }

    /**
     * Username rules: 3 to 20 characters, starts with a letter, then letters,
     * numbers or single underscores, at least three letters in total, not
     * reserved and not offensive.
     *
     * @return array<int, mixed>
     */
    public static function usernameRules(?int $ignoreUserId = null): array
    {
        $unique = \Illuminate\Validation\Rule::unique('users', 'username');

        return [
            'required', 'string', 'min:3', 'max:20',
            'regex:/^[a-z](?:[a-z0-9]|_(?!_))*[a-z0-9]$/',
            function (string $attribute, mixed $value, \Closure $fail): void {
                if (preg_match_all('/[a-z]/', (string) $value) < 3) {
                    $fail('Your username needs at least three letters.');
                }
            },
            \Illuminate\Validation\Rule::notIn(User::RESERVED_USERNAMES),
            self::noProfanity('That username'),
            $ignoreUserId ? $unique->ignore($ignoreUserId) : $unique,
        ];
    }

    /** @return array<string, string> */
    public static function usernameMessages(string $field = 'username'): array
    {
        return [
            "{$field}.regex" => 'Start with a letter, then use lowercase letters, numbers and single underscores (no underscore at the end).',
            "{$field}.min" => 'Usernames are 3 to 20 characters.',
            "{$field}.max" => 'Usernames are 3 to 20 characters.',
            "{$field}.not_in" => 'That username is reserved. Please pick another.',
            "{$field}.unique" => 'That username is taken. Please pick another.',
        ];
    }

    /**
     * Person names: letters (any alphabet, so Urdu names work), single spaces,
     * apostrophes, hyphens and dots. No digits or symbols.
     *
     * @return array<int, mixed>
     */
    public static function nameRules(): array
    {
        return [
            'required', 'string', 'min:2', 'max:60',
            'regex:/^\pL[\pL\pM]*(?:[ .\'-]\pL[\pL\pM]*)*\.?$/u',
            self::noProfanity('Your name'),
        ];
    }

    /** @return array<string, string> */
    public static function nameMessages(string $field = 'name'): array
    {
        return [
            "{$field}.regex" => 'Please use letters only, with single spaces, hyphens or apostrophes between words.',
            "{$field}.min" => 'Please enter your full name.',
        ];
    }

    /** Collapses the name typed in to single spaces. */
    public static function normaliseName(string $name): string
    {
        return trim(preg_replace('/\s+/u', ' ', $name) ?? $name);
    }

    /** Why a username cannot be used, or null when it can (for the live check). */
    public static function usernameProblem(string $name): ?string
    {
        return match (true) {
            strlen($name) < 3 || strlen($name) > 20 => 'Usernames are 3 to 20 characters.',
            ! preg_match('/^[a-z]/', $name) => 'Start with a letter.',
            ! preg_match('/^[a-z](?:[a-z0-9]|_(?!_))*[a-z0-9]$/', $name) => 'Lowercase letters, numbers and single underscores only, not at the end.',
            preg_match_all('/[a-z]/', $name) < 3 => 'Use at least three letters.',
            in_array($name, User::RESERVED_USERNAMES, true) => 'That username is reserved.',
            self::isProfane($name) => 'That username is not allowed.',
            default => null,
        };
    }

    private static function squeeze(string $text): string
    {
        return preg_replace('/(.)\1+/', '$1', $text) ?? $text;
    }
}
