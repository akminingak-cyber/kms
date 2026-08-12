<?php

declare(strict_types=1);

namespace Modules\Media\Domain;

/**
 * Which encoders this deployment is licensed to run.
 *
 * FFmpeg's own licence depends on how it was built: LGPL-2.1-or-later by
 * default, GPL-2.0-or-later once configured with `--enable-gpl`. The encoders
 * that matter most for AVC and HEVC are themselves GPL-or-commercial, so
 * enabling them changes the licence of the resulting binary — and the licence
 * of a binary a proprietary platform ships or exposes as a service is a
 * decision for counsel, not for engineering
 * (`docs/architecture/09-dependency-policy.md`).
 *
 * Separately and additionally, **codec patent licensing is not the same
 * question as software licensing**. An encoder under a permissive software
 * licence can still carry patent obligations for the codec it implements.
 *
 * None of that can be verified from this repository: vendor and registry
 * documentation is unreachable from the initialisation environment, and a
 * licence read from memory is not a licence that was read
 * (`CLAUDE.md` §1.4). So this class does not encode any conclusion about any
 * encoder. It holds an **allow-list that an operator records after clearance**,
 * and it is empty by default.
 *
 * The consequence is deliberate: **out of the box no ladder can be created at
 * all.** Absence of a cleared licence is a prohibition, never a permission
 * (`CLAUDE.md` §1.5). That is a worse first-run experience and a much better
 * outcome than a platform that quietly encodes with something it may not use.
 */
final readonly class EncoderPolicy
{
    /**
     * @param  array<string,array{codec:string,licence:string,requires_gpl_build:bool}>  $permitted
     *                                                                                               keyed by encoder name as FFmpeg knows it, e.g. `libx264`
     */
    public function __construct(private array $permitted) {}

    /**
     * @param  array<int,array<string,mixed>>  $configured  as recorded in `config/kms.php`
     */
    public static function fromConfig(array $configured): self
    {
        $permitted = [];

        foreach ($configured as $entry) {
            $encoder = (string) ($entry['encoder'] ?? '');

            // An entry missing its licence is not a cleared entry. Accepting it
            // would defeat the only purpose of this list.
            if ($encoder === '' || ($entry['licence'] ?? '') === '') {
                continue;
            }

            $permitted[$encoder] = [
                'codec' => (string) ($entry['codec'] ?? ''),
                'licence' => (string) $entry['licence'],
                'requires_gpl_build' => (bool) ($entry['requires_gpl_build'] ?? false),
            ];
        }

        return new self($permitted);
    }

    public function permits(string $encoder, string $codec): bool
    {
        return isset($this->permitted[$encoder]) && $this->permitted[$encoder]['codec'] === $codec;
    }

    public function licenceOf(string $encoder): ?string
    {
        return $this->permitted[$encoder]['licence'] ?? null;
    }

    /**
     * Whether the recorded set requires FFmpeg to be built with `--enable-gpl`.
     *
     * Surfaced so the build that runs in production can be checked against the
     * clearance that was actually given, rather than assumed to match.
     */
    public function requiresGplBuild(): bool
    {
        foreach ($this->permitted as $entry) {
            if ($entry['requires_gpl_build']) {
                return true;
            }
        }

        return false;
    }

    /** @return list<string> */
    public function encoders(): array
    {
        return array_keys($this->permitted);
    }

    public function isEmpty(): bool
    {
        return $this->permitted === [];
    }
}
