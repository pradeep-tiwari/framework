<?php

namespace Lightpack\Meter\Console\Commands;

use Lightpack\Console\Command;

class MeterStatusCommand extends Command
{
    public function run()
    {
        $name = $this->args->argument(0);
        $limit = (int) ($this->args->get('limit') ?? 0);

        if (! $name) {
            $this->output->newline();
            $this->output->error('Please provide a meter name.');
            $this->output->newline();

            return self::FAILURE;
        }

        $meter = meter($name);
        $value = $meter->value();

        $this->output->newline();
        $this->output->infoLabel('METER');
        $this->output->line('Name: ' . $name);
        $this->output->line('Value: ' . $value);

        if ($limit > 0) {
            $this->output->line('Limit: ' . $limit);
            $this->output->line('Remaining: ' . $meter->remaining($limit));
        }

        $this->output->newline();

        if ($this->args->has('reset')) {
            $meter->reset();
            $this->output->success('Meter reset.');
            $this->output->newline();
        }

        return self::SUCCESS;
    }
}
