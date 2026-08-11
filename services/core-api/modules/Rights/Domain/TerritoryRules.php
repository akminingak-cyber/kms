<?php

declare(strict_types=1);

namespace Modules\Rights\Domain;

/**
 * An ordered include/exclude ruleset over territories.
 *
 * Ordered rather than a flat list because exclusions must be able to override
 * inclusions: "all of the EU except Germany" is one agreement, not two, and
 * flattening it loses the ability to express the correction that arrives when
 * Germany is later added back.
 *
 * Evaluation is **default deny**: a territory that no rule mentions is not
 * permitted. Absence of a right is a prohibition, never a permission.
 */
final readonly class TerritoryRules
{
    /** @param list<array{effect:string, territories:list<string>}> $rules */
    private function __construct(public array $rules) {}

    /** @param list<array{effect:string, territories:list<string>}> $rules */
    public static function fromArray(array $rules): self
    {
        $normalised = [];

        foreach ($rules as $rule) {
            $effect = $rule['effect'] ?? '';

            if (! in_array($effect, ['include', 'exclude'], true)) {
                continue;
            }

            $territories = array_values(array_unique(array_map(
                static fn (string $t): string => strtoupper(trim($t)),
                $rule['territories'] ?? [],
            )));

            sort($territories);
            $normalised[] = ['effect' => $effect, 'territories' => $territories];
        }

        return new self($normalised);
    }

    /** Permits every territory. For content with no territorial restriction. */
    public static function worldwide(): self
    {
        return new self([['effect' => 'include', 'territories' => ['*']]]);
    }

    public function permits(string $territory): bool
    {
        $territory = strtoupper(trim($territory));
        $permitted = false;

        // Later rules win, so a correction appended to an agreement takes
        // effect without rewriting what came before.
        foreach ($this->rules as $rule) {
            $matches = in_array('*', $rule['territories'], true)
                || in_array($territory, $rule['territories'], true);

            if (! $matches) {
                continue;
            }

            $permitted = $rule['effect'] === 'include';
        }

        return $permitted;
    }

    /**
     * Content addressing, so identical rulesets are stored once and shared.
     *
     * This is what makes an agreement-wide territory correction a single-row
     * write rather than a rewrite of millions of projection rows.
     */
    public function hash(): string
    {
        return hash('sha256', json_encode($this->rules, JSON_THROW_ON_ERROR));
    }

    /** @return list<array{effect:string, territories:list<string>}> */
    public function toArray(): array
    {
        return $this->rules;
    }
}
