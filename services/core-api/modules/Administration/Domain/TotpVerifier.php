<?php

declare(strict_types=1);

namespace Modules\Administration\Domain;

/**
 * RFC 6238 time-based one-time passwords.
 *
 * Implemented here rather than pulled in as a dependency: the algorithm is
 * HMAC-SHA1 over a counter with a truncation step, it is fully specified, and
 * it is around thirty lines. It is not cryptography being invented — the
 * primitive is `hash_hmac`.
 *
 * Staff MFA is mandatory (docs/security/identity-and-access.md §5), so this is
 * on the only path by which a staff session can be created.
 */
final class TotpVerifier
{
    private const PERIOD = 30;

    private const DIGITS = 6;

    /** How many periods either side are accepted, for clock drift. */
    private const WINDOW = 1;

    public function verify(string $base32Secret, string $code, int $timestamp): bool
    {
        $code = preg_replace('/\D/', '', $code) ?? '';

        if (strlen($code) !== self::DIGITS) {
            return false;
        }

        $key = self::base32Decode($base32Secret);

        if ($key === null) {
            return false;
        }

        $counter = intdiv($timestamp, self::PERIOD);

        for ($offset = -self::WINDOW; $offset <= self::WINDOW; $offset++) {
            $candidate = self::generate($key, $counter + $offset);

            // Constant-time comparison: a timing side channel here would leak
            // the code digit by digit.
            if (hash_equals($candidate, $code)) {
                return true;
            }
        }

        return false;
    }

    /** Exposed for enrolment and for tests that need a valid code. */
    public function codeFor(string $base32Secret, int $timestamp): string
    {
        $key = self::base32Decode($base32Secret);

        if ($key === null) {
            return '';
        }

        return self::generate($key, intdiv($timestamp, self::PERIOD));
    }

    public static function generateSecret(int $bytes = 20): string
    {
        return self::base32Encode(random_bytes($bytes));
    }

    private static function generate(string $key, int $counter): string
    {
        $binary = pack('J', $counter);
        $hash = hash_hmac('sha1', $binary, $key, true);

        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $truncated = ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);

        return str_pad((string) ($truncated % (10 ** self::DIGITS)), self::DIGITS, '0', STR_PAD_LEFT);
    }

    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    private static function base32Encode(string $binary): string
    {
        $bits = '';

        foreach (str_split($binary) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }

        $out = '';

        foreach (str_split($bits, 5) as $chunk) {
            $out .= self::ALPHABET[bindec(str_pad($chunk, 5, '0', STR_PAD_RIGHT))];
        }

        return $out;
    }

    private static function base32Decode(string $secret): ?string
    {
        $secret = strtoupper(rtrim(trim($secret), '='));

        if ($secret === '') {
            return null;
        }

        $bits = '';

        foreach (str_split($secret) as $char) {
            $index = strpos(self::ALPHABET, $char);

            if ($index === false) {
                return null;
            }

            $bits .= str_pad(decbin($index), 5, '0', STR_PAD_LEFT);
        }

        $binary = '';

        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $binary .= chr(bindec($chunk));
            }
        }

        return $binary;
    }
}
