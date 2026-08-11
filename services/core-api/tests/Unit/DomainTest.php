<?php

declare(strict_types=1);

namespace Tests\Unit;

use InvalidArgumentException;
use Modules\Administration\Domain\TotpVerifier;
use Modules\Device\Domain\DeviceClass;
use Modules\Identity\Domain\AccountStatus;
use Modules\Identity\Domain\EmailAddress;
use Modules\Identity\Domain\PasswordPolicy;
use Modules\Shared\Domain\Identifier;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/** Pure domain logic. No framework, no database. */
final class DomainTest extends TestCase
{
    #[Test]
    public function email_addresses_normalise_to_lower_case_and_trim(): void
    {
        // The database has a plain unique index; it is only case-insensitive
        // because every write goes through here.
        $this->assertSame('user@example.test', EmailAddress::fromString('  User@Example.TEST ')->value);
    }

    #[Test]
    #[DataProvider('badEmails')]
    public function malformed_email_addresses_are_refused(string $input): void
    {
        $this->expectException(InvalidArgumentException::class);
        EmailAddress::fromString($input);
    }

    public static function badEmails(): array
    {
        return [
            'empty' => [''],
            'no at' => ['nope'],
            'no domain' => ['user@'],
            'too long' => [str_repeat('a', 315).'@example.test'],
        ];
    }

    #[Test]
    public function only_an_active_account_may_authenticate(): void
    {
        // Default deny: a status added later is refused until someone decides.
        $this->assertTrue(AccountStatus::Active->canAuthenticate());
        $this->assertFalse(AccountStatus::Suspended->canAuthenticate());
        $this->assertFalse(AccountStatus::Closed->canAuthenticate());
    }

    #[Test]
    public function password_policy_measures_length_not_composition(): void
    {
        $this->assertSame(['TOO_SHORT'], PasswordPolicy::violations('short', 12));
        $this->assertSame([], PasswordPolicy::violations('correct-horse-battery', 12));
        // Bounded, so one request cannot cost unbounded hashing time.
        $this->assertContains('TOO_LONG', PasswordPolicy::violations(str_repeat('a', 5000), 12));
    }

    #[Test]
    public function identifiers_accept_only_canonical_uuid_v7(): void
    {
        $valid = '0199c1a4-7e3b-7c21-9f4d-2b6a8e05d913';
        $this->assertSame($valid, Identifier::fromString($valid)->value);
        $this->assertSame($valid, Identifier::fromString(strtoupper($valid))->value);

        // A v4 identifier is well-formed but is not what this platform issues.
        $this->assertNull(Identifier::tryFromString('9c3f6f4a-2c3f-4f2a-9f4d-2b6a8e05d913'));
        $this->assertNull(Identifier::tryFromString('not-a-uuid'));
        $this->assertNull(Identifier::tryFromString(null));
    }

    #[Test]
    public function only_keyboardless_device_classes_require_pairing(): void
    {
        $this->assertTrue(DeviceClass::Tizen->requiresPairing());
        $this->assertTrue(DeviceClass::WebOs->requiresPairing());
        $this->assertTrue(DeviceClass::AndroidTv->requiresPairing());
        $this->assertFalse(DeviceClass::Web->requiresPairing());
        $this->assertFalse(DeviceClass::Mobile->requiresPairing());
    }

    #[Test]
    public function totp_accepts_the_current_code_and_tolerates_drift(): void
    {
        $verifier = new TotpVerifier;
        $secret = TotpVerifier::generateSecret();
        $now = 1_800_000_000;

        $this->assertTrue($verifier->verify($secret, $verifier->codeFor($secret, $now), $now));

        // Televisions and phones drift; one period either side is accepted.
        $this->assertTrue($verifier->verify($secret, $verifier->codeFor($secret, $now - 30), $now));
        $this->assertTrue($verifier->verify($secret, $verifier->codeFor($secret, $now + 30), $now));
    }

    #[Test]
    public function totp_rejects_stale_and_malformed_codes(): void
    {
        $verifier = new TotpVerifier;
        $secret = TotpVerifier::generateSecret();
        $now = 1_800_000_000;

        // Two periods out is outside the tolerated window.
        $this->assertFalse($verifier->verify($secret, $verifier->codeFor($secret, $now - 120), $now));
        $this->assertFalse($verifier->verify($secret, '000', $now));
        $this->assertFalse($verifier->verify($secret, 'abcdef', $now));
        $this->assertFalse($verifier->verify('not base32 !!', '123456', $now));
    }

    #[Test]
    public function a_totp_code_from_another_secret_is_rejected(): void
    {
        $verifier = new TotpVerifier;
        $mine = TotpVerifier::generateSecret();
        $theirs = TotpVerifier::generateSecret();
        $now = 1_800_000_000;

        $this->assertFalse($verifier->verify($mine, $verifier->codeFor($theirs, $now), $now));
    }
}
