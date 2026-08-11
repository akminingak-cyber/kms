<?php

declare(strict_types=1);

namespace Modules\Shared\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * An RFC 9457 problem document.
 *
 * Thrown rather than returned, so a failure deep in a use case cannot be
 * accidentally ignored by a caller that forgot to check a return value.
 */
final class ApiProblem extends RuntimeException
{
    /** @param  array<string,mixed>  $meta */
    private function __construct(
        public readonly ErrorCode $errorCode,
        public readonly array $meta = [],
        public readonly ?string $detail = null,
    ) {
        parent::__construct($errorCode->title());
    }

    /** @param  array<string,mixed>  $meta */
    public static function of(ErrorCode $code, array $meta = [], ?string $detail = null): self
    {
        return new self($code, $meta, $detail);
    }

    /**
     * Validation failures list every field that failed, not just the first —
     * a client that has to submit five times to discover five problems is a
     * client whose users give up.
     */
    public static function fromValidation(ValidationException $exception): self
    {
        $errors = [];

        foreach ($exception->errors() as $field => $messages) {
            foreach ((array) $messages as $message) {
                $errors[] = ['field' => (string) $field, 'code' => self::ruleCode((string) $message)];
            }
        }

        return new self(ErrorCode::ValidationFailed, ['errors' => $errors]);
    }

    /**
     * Validation messages are prose and therefore not a contract. Clients
     * localise from a code, so the prose is reduced to a stable token.
     */
    private static function ruleCode(string $message): string
    {
        return match (true) {
            str_contains($message, 'required') => 'REQUIRED',
            str_contains($message, 'must be a valid email') => 'INVALID_FORMAT',
            str_contains($message, 'already been taken') => 'ALREADY_TAKEN',
            str_contains($message, 'must be at least') => 'TOO_SHORT',
            str_contains($message, 'may not be greater') || str_contains($message, 'must not be greater') => 'TOO_LONG',
            str_contains($message, 'selected') && str_contains($message, 'invalid') => 'NOT_ALLOWED',
            str_contains($message, 'must be a string') => 'INVALID_TYPE',
            str_contains($message, 'format is invalid') => 'INVALID_FORMAT',
            default => 'INVALID',
        };
    }

    public function toResponse(string $correlationId, string $instance): JsonResponse
    {
        $body = [
            'type' => $this->errorCode->type(),
            'title' => $this->errorCode->title(),
            'status' => $this->errorCode->status(),
            'code' => $this->errorCode->value,
            'instance' => $instance,
            'correlation_id' => $correlationId,
        ];

        if ($this->detail !== null) {
            $body['detail'] = $this->detail;
        }

        if ($this->meta !== []) {
            $body['meta'] = $this->meta;
        }

        return new JsonResponse(
            $body,
            $this->errorCode->status(),
            ['Content-Type' => 'application/problem+json'],
        );
    }
}
