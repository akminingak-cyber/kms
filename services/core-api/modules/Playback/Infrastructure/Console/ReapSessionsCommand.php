<?php

declare(strict_types=1);

namespace Modules\Playback\Infrastructure\Console;

use Illuminate\Console\Command;
use Modules\Playback\Application\SessionReaper;

/**
 * Closes playback sessions whose client stopped heartbeating.
 *
 * Scheduled rather than event-driven, because the event we are reacting to is
 * the *absence* of one. It is idempotent and bounded, so running it more often
 * than necessary costs a query and running it less often only widens the window
 * in which the session record overstates what is being watched.
 */
final class ReapSessionsCommand extends Command
{
    protected $signature = 'kms:playback:reap-sessions {--limit=1000 : Maximum sessions to close in one run}';

    protected $description = 'Close playback sessions whose heartbeat has lapsed and release their concurrency slots';

    public function handle(SessionReaper $reaper): int
    {
        $result = $reaper->reap((int) $this->option('limit'));

        $this->info(sprintf(
            'Closed %d lapsed session(s); released %d durable slot(s).',
            $result['sessions'],
            $result['slots'],
        ));

        return self::SUCCESS;
    }
}
