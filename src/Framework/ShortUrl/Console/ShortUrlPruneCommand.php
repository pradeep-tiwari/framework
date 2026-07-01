<?php

namespace Lightpack\ShortUrl\Console;

use Lightpack\Console\Command;
use Lightpack\ShortUrl\ShortUrl;

class ShortUrlPruneCommand extends Command
{
    public function run()
    {
        $days = (int) ($this->args->get('days') ?? 30);
        $force = $this->args->has('force');

        $this->output->newline();
        $this->output->infoLabel('SHORT URL PRUNE');
        $this->output->newline();

        $cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $query = ShortUrl::query()
            ->where('expires_at', '<', $cutoff)
            ->whereNotNull('expires_at');

        $count = $query->count();

        if ($count === 0) {
            $this->output->success("✔ No expired short URLs to prune");
            $this->output->newline();

            return self::SUCCESS;
        }

        $this->output->line("Found {$count} expired short URL(s) older than {$days} days");
        $this->output->newline();

        if (! $force) {
            $confirm = $this->prompt->confirm('Are you sure you want to delete them?', false);

            if (! $confirm) {
                $this->output->success("✔ Prune cancelled");
                $this->output->newline();

                return self::SUCCESS;
            }
        }

        $deleted = $query->delete();

        $this->output->success("✔ Pruned {$deleted} expired short URL(s)");
        $this->output->newline();

        return self::SUCCESS;
    }
}
