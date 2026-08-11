<?php

declare(strict_types=1);

namespace Modules\Profile\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Profile\Application\ProfileService;
use Modules\Profile\Http\Requests\StoreProfileRequest;
use Modules\Profile\Http\Requests\UpdateProfileRequest;
use Modules\Profile\Infrastructure\Eloquent\Profile;
use Modules\Shared\Domain\Identifier;
use Modules\Shared\Http\ApiProblem;
use Modules\Shared\Http\ErrorCode;

final class ProfileController
{
    public function __construct(private readonly ProfileService $profiles) {}

    public function index(Request $request): JsonResponse
    {
        $accountUuid = $this->accountUuid($request);

        return new JsonResponse([
            'data' => array_map(
                $this->present(...),
                $this->profiles->listForAccount($accountUuid),
            ),
            'limit' => (int) config('kms.profiles.max_per_account'),
        ]);
    }

    public function store(StoreProfileRequest $request): JsonResponse
    {
        $profile = $this->profiles->create($this->accountUuid($request), [
            'name' => $request->string('name')->toString(),
            'locale' => $request->string('locale', 'en')->toString(),
            'max_rating' => $request->input('max_rating'),
            'pin' => $request->input('pin'),
        ]);

        return new JsonResponse(['data' => $this->present($profile)], 201);
    }

    public function show(Request $request, string $profileId): JsonResponse
    {
        $this->assertIdentifier($profileId);

        $profile = $this->profiles->findOwned($this->accountUuid($request), $profileId);

        return new JsonResponse(['data' => $this->present($profile)]);
    }

    public function update(UpdateProfileRequest $request, string $profileId): JsonResponse
    {
        $this->assertIdentifier($profileId);

        $profile = $this->profiles->update(
            $this->accountUuid($request),
            $profileId,
            $request->only(['name', 'locale', 'max_rating', 'pin', 'audio_language', 'subtitle_language']),
        );

        return new JsonResponse(['data' => $this->present($profile)]);
    }

    public function destroy(Request $request, string $profileId): JsonResponse
    {
        $this->assertIdentifier($profileId);

        $this->profiles->delete($this->accountUuid($request), $profileId);

        return new JsonResponse(null, 204);
    }

    /** @return array<string,mixed> */
    private function present(Profile $profile): array
    {
        return [
            'id' => $profile->uuid,
            'name' => $profile->name,
            'is_primary' => $profile->is_primary,
            'locale' => $profile->locale,
            'max_rating' => $profile->max_rating,
            // Whether a PIN is set is safe to disclose to its owner; the PIN is not.
            'has_pin' => $profile->hasPin(),
            'audio_language' => $profile->audio_language,
            'subtitle_language' => $profile->subtitle_language,
        ];
    }

    private function accountUuid(Request $request): string
    {
        return (string) $request->attributes->get('kms.account_uuid');
    }

    private function assertIdentifier(string $value): void
    {
        if (Identifier::tryFromString($value) === null) {
            // Malformed identifiers are "not found", not "invalid": the caller
            // learns nothing about which identifiers exist.
            throw ApiProblem::of(ErrorCode::ProfileNotFound);
        }
    }
}
