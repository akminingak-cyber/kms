<?php

declare(strict_types=1);

namespace Modules\Media\Infrastructure;

use Illuminate\Support\Facades\Storage;
use Modules\Media\Contracts\DeviceProfiles;
use Modules\Media\Contracts\ManifestStore;
use Modules\Media\Contracts\MediaPublications;
use Modules\Media\Domain\EncoderPolicy;
use Modules\Shared\Infrastructure\ModuleServiceProvider;

final class MediaServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Media';
    }

    protected function registerBindings(): void
    {
        $this->app->bind(MediaPublications::class, MediaPublicationDirectory::class);
        $this->app->bind(DeviceProfiles::class, ConfiguredDeviceProfiles::class);

        // Bound per resolution rather than as a singleton: the disk is
        // configuration, and capturing it once would pin the store to whichever
        // disk happened to be configured the first time anything published.
        $this->app->bind(ManifestStore::class, static fn (): ManifestStore => new FilesystemManifestStore(
            Storage::disk((string) config('kms.media.manifest_disk')),
        ));

        $this->app->singleton(EncoderPolicy::class, static fn (): EncoderPolicy => EncoderPolicy::fromConfig(
            (array) config('kms.media.permitted_encoders', []),
        ));
    }
}
