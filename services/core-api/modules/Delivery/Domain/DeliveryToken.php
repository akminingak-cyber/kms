<?php

declare(strict_types=1);

namespace Modules\Delivery\Domain;

use InvalidArgumentException;

/**
 * The delivery token.
 *
 * Issued **only** by playback authorization (ADR-0006) and validated at the edge
 * — the edge validates, it never decides. A CDN that decides who may watch is a
 * CDN that cannot be replaced and a control we cannot audit
 * (`docs/streaming/packaging-and-delivery.md` §6).
 *
 * ## One opaque parameter, not three
 *
 * The token, the expiry and the session all have to reach the edge, and every
 * one of them varies per viewer. Carried as three query parameters, an edge
 * configuration has three separate things to exclude from its cache key — and
 * missing any one of them gives every viewer a private copy of every segment,
 * collapsing cache offload to zero and turning origin egress into the largest
 * line on the invoice. The failure is invisible until that invoice arrives.
 *
 * So everything variable is packed into a single parameter. One thing to
 * exclude, and a misconfiguration that is hard to make and obvious when made.
 *
 * ## Scoped to a prefix, not a file
 *
 * A player fetches one manifest and then thousands of segments; it cannot
 * obtain a token per object. The token is bound to the publication's path
 * prefix, which is the smallest scope that is actually workable, and the prefix
 * is matched with a boundary so a token for `/live/{a}` cannot be replayed
 * against `/live/{a}-other`.
 */
final readonly class DeliveryToken
{
    public const PARAMETER = 't';

    private const VERSION = 'v1';

    /** 128 bits of a SHA-256 MAC: enough that forgery is infeasible, short enough to keep URLs sane. */
    private const MAC_LENGTH = 32;

    public function __construct(private string $key)
    {
        if ($key === '') {
            throw new InvalidArgumentException(
                'No delivery token key is configured. Playback would issue tokens the edge cannot verify.',
            );
        }
    }

    public function issue(string $originPrefix, string $sessionUuid, int $expiresAt): string
    {
        $payload = self::VERSION.':'.$expiresAt.':'.$sessionUuid;

        return $this->base64UrlEncode($payload.':'.$this->mac($originPrefix, $sessionUuid, $expiresAt));
    }

    /**
     * Verifies a token against the prefix it must have been issued for.
     *
     * The signature is checked **before** the expiry, and with a constant-time
     * comparison. Reporting "expired" for a forged token would confirm to an
     * attacker that everything except the timestamp was accepted.
     */
    public function verify(string $originPrefix, string $token, int $now): DeliveryTokenVerdict
    {
        $decoded = $this->base64UrlDecode($token);

        if ($decoded === null) {
            return DeliveryTokenVerdict::invalid();
        }

        $parts = explode(':', $decoded);

        if (count($parts) !== 4 || $parts[0] !== self::VERSION) {
            return DeliveryTokenVerdict::invalid();
        }

        [, $expiresAt, $sessionUuid, $mac] = $parts;

        if (preg_match('/^\d{1,12}$/', $expiresAt) !== 1) {
            return DeliveryTokenVerdict::invalid();
        }

        if (! hash_equals($this->mac($originPrefix, $sessionUuid, (int) $expiresAt), $mac)) {
            return DeliveryTokenVerdict::invalid();
        }

        if ((int) $expiresAt <= $now) {
            return DeliveryTokenVerdict::expired($sessionUuid);
        }

        return DeliveryTokenVerdict::valid($sessionUuid, (int) $expiresAt);
    }

    private function mac(string $originPrefix, string $sessionUuid, int $expiresAt): string
    {
        /*
         * Length-prefixed rather than delimiter-joined. Concatenating fields
         * with a separator that can occur inside a field lets two different
         * inputs produce one signed string, and a path is entirely capable of
         * containing whatever separator was chosen.
         */
        $message = implode('', array_map(
            static fn (string $field): string => strlen($field).':'.$field,
            [self::VERSION, $originPrefix, $sessionUuid, (string) $expiresAt],
        ));

        return substr(hash_hmac('sha256', $message, $this->key), 0, self::MAC_LENGTH);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): ?string
    {
        if (preg_match('/^[A-Za-z0-9_-]{1,512}$/', $value) !== 1) {
            return null;
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        return $decoded === false ? null : $decoded;
    }
}
