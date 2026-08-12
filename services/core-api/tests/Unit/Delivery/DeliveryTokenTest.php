<?php

declare(strict_types=1);

namespace Tests\Unit\Delivery;

use InvalidArgumentException;
use Modules\Delivery\Domain\DeliveryToken;
use Modules\Delivery\Domain\DeliveryTokenVerdict;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The delivery token.
 *
 * The edge validates it; the edge never decides. So the properties that matter
 * are that a token cannot be forged, cannot be replayed against content it was
 * not issued for, and stops working when it expires.
 */
final class DeliveryTokenTest extends TestCase
{
    private const PREFIX = '/live/019ff0f4-0000-7000-8000-000000000001';

    private const SESSION = '019ff0f4-0000-7000-8000-0000000000aa';

    #[Test]
    public function a_token_verifies_against_the_prefix_it_was_issued_for(): void
    {
        $token = $this->token();
        $issued = $token->issue(self::PREFIX, self::SESSION, 2_000_000_000);

        $verdict = $token->verify(self::PREFIX, $issued, 1_999_999_000);

        $this->assertTrue($verdict->isValid());
        $this->assertSame(self::SESSION, $verdict->sessionUuid);
    }

    #[Test]
    public function a_token_does_not_verify_against_another_publication(): void
    {
        $token = $this->token();
        $issued = $token->issue(self::PREFIX, self::SESSION, 2_000_000_000);

        // A token for one channel must not open another, or a viewer entitled
        // to anything is entitled to everything.
        $verdict = $token->verify('/live/019ff0f4-0000-7000-8000-000000000002', $issued, 1_999_999_000);

        $this->assertSame(DeliveryTokenVerdict::INVALID, $verdict->outcome);
    }

    #[Test]
    public function a_prefix_is_not_matched_by_a_longer_sibling_path(): void
    {
        $token = $this->token();
        $issued = $token->issue(self::PREFIX, self::SESSION, 2_000_000_000);

        // Without a boundary check, a token for `/live/{a}` would validate
        // `/live/{a}-other`. The signature is over the exact prefix, so it does
        // not.
        $this->assertFalse($token->verify(self::PREFIX.'-other', $issued, 1_999_999_000)->isValid());
    }

    #[Test]
    public function an_expired_token_is_refused(): void
    {
        $token = $this->token();
        $issued = $token->issue(self::PREFIX, self::SESSION, 1_000);

        $verdict = $token->verify(self::PREFIX, $issued, 1_001);

        $this->assertSame(DeliveryTokenVerdict::EXPIRED, $verdict->outcome);
        $this->assertFalse($verdict->isValid());
    }

    #[Test]
    public function a_forged_expiry_is_reported_as_forgery_rather_than_expiry(): void
    {
        $token = $this->token();
        $issued = $token->issue(self::PREFIX, self::SESSION, 1_000);

        // Extend the expiry inside the token and re-encode it.
        $decoded = (string) base64_decode(strtr($issued, '-_', '+/'), true);
        $tampered = rtrim(strtr(base64_encode(
            str_replace(':1000:', ':2000000000:', $decoded),
        ), '+/', '-_'), '=');

        $verdict = $token->verify(self::PREFIX, $tampered, 1_001);

        /*
         * The signature is checked before the expiry, so this is INVALID and
         * not EXPIRED. Reporting "expired" would confirm to an attacker that
         * everything except the timestamp was accepted, and it would also put
         * a forgery into the expiry metric — where it would be read as clients
         * failing to renew rather than as an attack.
         */
        $this->assertSame(DeliveryTokenVerdict::INVALID, $verdict->outcome);
    }

    #[Test]
    public function a_token_signed_with_another_key_is_refused(): void
    {
        $issued = (new DeliveryToken('one-key'))->issue(self::PREFIX, self::SESSION, 2_000_000_000);

        $this->assertFalse(
            (new DeliveryToken('another-key'))->verify(self::PREFIX, $issued, 1_999_999_000)->isValid(),
        );
    }

    #[Test]
    public function malformed_input_is_refused_rather_than_parsed(): void
    {
        $token = $this->token();

        foreach (['', 'not base64!', 'YWJj', str_repeat('A', 600)] as $candidate) {
            $this->assertFalse(
                $token->verify(self::PREFIX, $candidate, 1_000)->isValid(),
                "expected '{$candidate}' to be refused",
            );
        }
    }

    #[Test]
    public function a_missing_key_fails_loudly_at_construction(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/the edge cannot verify/');

        // Better than issuing tokens signed with an empty string, which would
        // verify perfectly against our own origin and against nothing else.
        new DeliveryToken('');
    }

    private function token(): DeliveryToken
    {
        return new DeliveryToken('test-delivery-key-not-a-secret');
    }
}
