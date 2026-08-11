<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Generates the Ed25519 keypair used to sign access tokens.
 *
 * The key is printed, never written to a file and never committed. Where it is
 * stored is an environment concern: locally it goes in `.env` (git-ignored), in
 * every other environment it is injected from the secret manager
 * (docs/security/secrets-and-key-management.md).
 */
final class GenerateTokenKey extends Command
{
    protected $signature = 'kms:generate-token-key';

    protected $description = 'Generate an Ed25519 signing key for access tokens';

    public function handle(): int
    {
        $keypair = sodium_crypto_sign_keypair();

        $this->line('');
        $this->info('Add this to your environment (never to version control):');
        $this->line('');
        $this->line('KMS_TOKEN_SIGNING_KEY='.base64_encode(sodium_crypto_sign_secretkey($keypair)));
        $this->line('');
        $this->comment('Public key (for a future JWKS endpoint; safe to publish):');
        $this->line(base64_encode(sodium_crypto_sign_publickey($keypair)));
        $this->line('');

        return self::SUCCESS;
    }
}
