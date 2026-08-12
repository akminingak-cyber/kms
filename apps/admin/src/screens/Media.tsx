import { ApiError } from '@kms/ts-api-client';
import { useCallback, useState } from 'react';

import { useSession } from '../api/SessionProvider.js';
import { useResource } from '../api/useResource.js';
import { DataTable } from '../components/DataTable.js';
import { ProblemNotice } from '../components/ProblemNotice.js';
import { Screen } from '../components/Screen.js';
import { formatInstant, type Ladder, type PackagingProfile, type Publication } from '../types.js';

export function Media() {
  const { client } = useSession();
  const [actionError, setActionError] = useState<ApiError | null>(null);
  const [busy, setBusy] = useState<string | null>(null);

  const ladders = useResource(
    useCallback(async () => {
      const { data } = await client.GET('/media/ladders');

      return data?.data ?? [];
    }, [client]),
  );

  const profiles = useResource(
    useCallback(async () => {
      const { data } = await client.GET('/media/packaging-profiles', {
        params: { query: { limit: 100 } },
      });

      return data?.data ?? [];
    }, [client]),
  );

  const publications = useResource(
    useCallback(async () => {
      const { data } = await client.GET('/media/publications', {
        params: { query: { limit: 100 } },
      });

      return data?.data ?? [];
    }, [client]),
  );

  async function act(id: string, action: 'publish' | 'retire') {
    setActionError(null);
    setBusy(id);

    try {
      if (action === 'publish') {
        await client.POST('/media/publications/{publicationId}/publish', {
          params: { path: { publicationId: id } },
        });
      } else {
        await client.POST('/media/publications/{publicationId}/retire', {
          params: { path: { publicationId: id } },
        });
      }

      publications.reload();
    } catch (caught) {
      if (caught instanceof ApiError) {
        setActionError(caught);

        return;
      }

      throw caught;
    } finally {
      setBusy(null);
    }
  }

  return (
    <Screen
      title="Media"
      intro={
        <>
          <p>
            The configuration of the media plane — what to encode, how to cut it, and where the
            result is addressed. Nothing here encodes or serves anything; the pipeline that consumes
            this runs elsewhere.
          </p>
          <p className="muted">
            Publishing generates one manifest per quality class per format. That is how a licensor's
            resolution cap is enforced: a capped viewer receives a manifest that does not mention
            the rungs they may not have, and the client is never asked to filter its own renditions.
          </p>
        </>
      }
      error={ladders.error ?? profiles.error ?? publications.error}
      onRetry={() => {
        ladders.reload();
        profiles.reload();
        publications.reload();
      }}
    >
      {actionError !== null && <ProblemNotice error={actionError} />}

      <h2>Publications</h2>
      <DataTable<Publication>
        loading={publications.loading}
        rows={publications.data ?? []}
        empty="Nothing published. A channel nobody has encoded is a denial, not an empty stream."
        columns={[
          {
            header: 'Origin path',
            render: (row) => <code>{row.origin_prefix}</code>,
          },
          { header: 'Ladder', render: (row) => row.ladder?.slug ?? '—' },
          {
            header: 'Packaging',
            render: (row) => row.packaging_profile?.slug ?? '—',
          },
          {
            header: 'Status',
            render: (row) => (
              <span className={row.status === 'published' ? 'badge badge--allow' : 'badge'}>
                {row.status}
              </span>
            ),
          },
          { header: 'Rev.', render: (row) => row.revision ?? 0 },
          { header: 'Manifests', render: (row) => row.manifests?.length ?? 0 },
          {
            header: 'Published',
            render: (row) => formatInstant(row.published_at),
          },
          {
            header: '',
            render: (row) => (
              <span className="row-actions">
                <button
                  type="button"
                  disabled={busy === row.id}
                  onClick={() => row.id !== undefined && act(row.id, 'publish')}
                >
                  {row.status === 'published' ? 'Republish' : 'Publish'}
                </button>
                {row.status === 'published' && (
                  <button
                    type="button"
                    disabled={busy === row.id}
                    onClick={() => row.id !== undefined && act(row.id, 'retire')}
                  >
                    Retire
                  </button>
                )}
              </span>
            ),
          },
        ]}
      />

      <h2>Encoding ladders</h2>
      <DataTable<Ladder>
        loading={ladders.loading}
        rows={ladders.data ?? []}
        empty="No ladders. None can be created until an encoder licence clearance has been recorded — the permitted list is empty by default and an empty list permits nothing."
        columns={[
          { header: 'Slug', render: (row) => <code>{row.slug}</code> },
          { header: 'Name', render: (row) => row.name },
          { header: 'Content class', render: (row) => row.content_class },
          {
            header: 'Rungs',
            render: (row) =>
              (row.rungs ?? [])
                .map((rung) => `${rung.height}p@${rung.video_bitrate_kbps}k`)
                .join(' · ') || '—',
          },
          {
            header: 'Audio',
            render: (row) => (row.audio ?? []).map((track) => track.language).join(', ') || '—',
          },
        ]}
      />

      <h2>Packaging profiles</h2>
      <DataTable<PackagingProfile>
        loading={profiles.loading}
        rows={profiles.data ?? []}
        empty="No packaging profiles."
        columns={[
          { header: 'Slug', render: (row) => <code>{row.slug}</code> },
          { header: 'Container', render: (row) => row.container },
          {
            header: 'Segment',
            render: (row) => `${row.segment_duration_ms ?? 0} ms`,
          },
          { header: 'GOP', render: (row) => `${row.gop_duration_ms ?? 0} ms` },
          {
            header: 'Encryption',
            /*
             * Null means unencrypted, and is shown as such. DRM is a later
             * phase, and a placeholder here would advertise protection that is
             * not applied.
             */
            render: (row) =>
              row.encryption_scheme ?? <span className="muted">none (DRM is a later phase)</span>,
          },
        ]}
      />
    </Screen>
  );
}
