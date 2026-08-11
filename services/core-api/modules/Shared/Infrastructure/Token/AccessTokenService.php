<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Token;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Str;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Eddsa;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Builder;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\StrictValidAt;
use Lcobucci\JWT\Validation\Validator;
use Modules\Shared\Domain\Clock;
use Psr\Clock\ClockInterface;
use RuntimeException;

/**
 * Access tokens: short-lived, asymmetrically signed, validated without a
 * database read.
 *
 * That last property is a boundary decision, not an optimisation. If validating
 * a token required reading the `identity` schema, the identity store would
 * become a hard dependency of every authenticated request — and from Phase 4,
 * of the playback hot path, whose database role deliberately has no access to
 * it (docs/security/playback-authorization.md §7).
 *
 * Revocation is the deliberate trade: a revoked token stays technically valid
 * until it expires unless its id is in the revocation set, which is a small
 * Redis set with a TTL equal to the access-token lifetime. Bounded, cheap to
 * check, and it never grows without limit.
 */
final class AccessTokenService
{
    private const REVOCATION_PREFIX = 'kms:token:revoked:';

    public function __construct(
        private readonly Clock $clock,
        private readonly CacheRepository $cache,
    ) {}

    /**
     * @param  array<string,scalar>  $claims
     * @return array{token:string, jti:string, expires_at:DateTimeImmutable}
     */
    public function issue(string $subject, string $deviceUuid, array $claims = [], ?int $ttlSeconds = null): array
    {
        $now = $this->clock->now();
        $ttl = $ttlSeconds ?? (int) config('kms.tokens.access_ttl_seconds');
        $expiresAt = $now->modify("+{$ttl} seconds");
        $jti = (string) Str::uuid7();

        $builder = (new Builder(new JoseEncoder, ChainedFormatter::default()))
            ->issuedBy((string) config('kms.tokens.issuer'))
            ->permittedFor((string) config('kms.tokens.audience'))
            ->identifiedBy($jti)
            ->issuedAt($now)
            ->canOnlyBeUsedAfter($now)
            ->expiresAt($expiresAt)
            ->relatedTo($subject)
            // Device binding. A token lifted from one device and replayed from
            // another still names the device it was issued to, and every
            // authorization decision records it.
            ->withClaim('dev', $deviceUuid)
            ->withHeader('kid', (string) config('kms.tokens.signing_key_id'));

        foreach ($claims as $name => $value) {
            $builder = $builder->withClaim($name, $value);
        }

        $token = $builder->getToken(new Eddsa, $this->signingKey());

        return ['token' => $token->toString(), 'jti' => $jti, 'expires_at' => $expiresAt];
    }

    /**
     * @return array{sub:string, dev:string, jti:string, claims:array<string,mixed>}
     *
     * @throws InvalidAccessToken
     */
    public function verify(string $jwt): array
    {
        try {
            $token = (new Parser(new JoseEncoder))->parse($jwt);
        } catch (\Throwable) {
            throw InvalidAccessToken::malformed();
        }

        if (! $token instanceof UnencryptedToken) {
            throw InvalidAccessToken::malformed();
        }

        $leeway = new \DateInterval('PT'.(int) config('kms.tokens.leeway_seconds').'S');

        $violations = (new Validator)->validate(
            $token,
            new SignedWith(new Eddsa, $this->verificationKey()),
            new IssuedBy((string) config('kms.tokens.issuer')),
            new PermittedFor((string) config('kms.tokens.audience')),
            new StrictValidAt($this->psrClock(), $leeway),
        );

        if ($violations !== true) {
            // Distinguish expiry from every other failure: a client that knows
            // its token merely expired refreshes silently, where one told
            // "invalid" should sign the user out.
            throw $token->isExpired($this->clock->now())
                ? InvalidAccessToken::expired()
                : InvalidAccessToken::malformed();
        }

        $jti = $token->claims()->get('jti');
        $subject = $token->claims()->get('sub');
        $device = $token->claims()->get('dev');

        if (! is_string($jti) || ! is_string($subject) || ! is_string($device)) {
            throw InvalidAccessToken::malformed();
        }

        if ($this->cache->has(self::REVOCATION_PREFIX.$jti)) {
            throw InvalidAccessToken::revoked();
        }

        return [
            'sub' => $subject,
            'dev' => $device,
            'jti' => $jti,
            'claims' => $token->claims()->all(),
        ];
    }

    /**
     * Bounded-lifetime revocation. The entry only needs to outlive the token
     * it revokes, so the set can never grow without limit.
     */
    public function revoke(string $jti): void
    {
        $this->cache->put(
            self::REVOCATION_PREFIX.$jti,
            true,
            (int) config('kms.tokens.revocation_ttl_seconds'),
        );
    }

    /** The public half, for a future JWKS endpoint and for verification. */
    public function publicKeyBase64(): string
    {
        return base64_encode(sodium_crypto_sign_publickey_from_secretkey($this->rawSecretKey()));
    }

    private function signingKey(): InMemory
    {
        return InMemory::plainText($this->rawSecretKey());
    }

    private function verificationKey(): InMemory
    {
        return InMemory::plainText(sodium_crypto_sign_publickey_from_secretkey($this->rawSecretKey()));
    }

    private function rawSecretKey(): string
    {
        $configured = config('kms.tokens.signing_key');

        if (! is_string($configured) || $configured === '') {
            // Fails loudly at first use rather than silently signing with
            // something predictable. There is deliberately no default.
            throw new RuntimeException(
                'KMS_TOKEN_SIGNING_KEY is not set. Generate one with: php artisan kms:generate-token-key'
            );
        }

        $raw = base64_decode($configured, true);

        if ($raw === false || strlen($raw) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
            throw new RuntimeException('KMS_TOKEN_SIGNING_KEY is not a valid base64 Ed25519 secret key.');
        }

        return $raw;
    }

    private function psrClock(): ClockInterface
    {
        $clock = $this->clock;

        return new class($clock) implements ClockInterface
        {
            public function __construct(private readonly Clock $inner) {}

            public function now(): DateTimeImmutable
            {
                return $this->inner->now()->setTimezone(new DateTimeZone('UTC'));
            }
        };
    }
}
