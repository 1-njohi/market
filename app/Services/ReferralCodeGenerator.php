<?php

namespace App\Services;

use RuntimeException;

/**
 * Generates human-shareable referral codes of the form PREFIX-XXXX.
 *
 * PREFIX is derived from the user's first name (uppercased, alphanumeric
 * only, truncated to 5 chars). If the first name yields fewer than 2
 * usable characters, a random 4-char prefix is used instead.
 *
 * XXXX is a random 4-char uppercase alphanumeric suffix.
 *
 * The generator is pure — it delegates uniqueness to a caller-supplied
 * callable so it never touches the database directly. That keeps it fast
 * to unit test and free of Eloquent coupling.
 */
class ReferralCodeGenerator
{
    private const PREFIX_MAX = 5;
    private const PREFIX_MIN = 2;
    private const SUFFIX_LENGTH = 4;
    private const MAX_ATTEMPTS = 20;
    private const CHARSET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    /**
     * @param  callable(string): bool  $isTaken  Returns true if the code
     *                                            is already in use.
     *
     * @throws RuntimeException  If no unique code can be generated.
     */
    public function generate(string $name, callable $isTaken): string
    {
        $prefix = $this->prefixFromName($name);

        for ($i = 0; $i < self::MAX_ATTEMPTS; $i++) {
            $code = $prefix . '-' . $this->randomChars(self::SUFFIX_LENGTH);

            if (!$isTaken($code)) {
                return $code;
            }
        }

        throw new RuntimeException(
            'Could not generate a unique referral code after '
            . self::MAX_ATTEMPTS . ' attempts.'
        );
    }

    private function prefixFromName(string $name): string
    {
        $first = preg_split('/\s+/', trim($name))[0] ?? '';
        $clean = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $first) ?? '');

        if (strlen($clean) < self::PREFIX_MIN) {
            return $this->randomChars(4);
        }

        return substr($clean, 0, self::PREFIX_MAX);
    }

    private function randomChars(int $length): string
    {
        $out = '';
        $max = strlen(self::CHARSET) - 1;

        for ($i = 0; $i < $length; $i++) {
            $out .= self::CHARSET[random_int(0, $max)];
        }

        return $out;
    }
}