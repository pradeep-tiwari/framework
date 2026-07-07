<?php

namespace Lightpack\Meter;

use Lightpack\Meter\Events\MeterExceeded;
use Lightpack\Meter\Events\MeterReset;

class Meter
{
    public function __construct(
        protected MeterDriverInterface $driver,
        protected string $name,
        protected ?string $period = null,
    ) {
    }

    /**
     * Increment the meter by the given amount.
     */
    public function hit(int $amount = 1): void
    {
        $this->driver->increment($this->key(), $amount);
    }

    /**
     * Get the current meter value.
     */
    public function value(): int
    {
        return $this->driver->value($this->key());
    }

    /**
     * Get the remaining allowance against a limit.
     */
    public function remaining(int $limit): int
    {
        return max(0, $limit - $this->value());
    }

    /**
     * Check if the meter is currently below a limit.
     */
    public function available(int $limit): bool
    {
        return $this->value() < $limit;
    }

    /**
     * Increment the meter only if the resulting value stays within the limit.
     * Returns true on success, false if the limit would be exceeded.
     */
    public function consume(int $amount, int $limit): bool
    {
        $current = $this->value();

        if ($current + $amount > $limit) {
            $this->fireEvent('meter.exceeded', new MeterExceeded(
                $this->name,
                $this->period,
                $this->key(),
                $limit,
                $amount,
                $current,
            ));

            return false;
        }

        $this->hit($amount);

        return true;
    }

    /**
     * Reset the meter value to zero.
     */
    public function reset(): void
    {
        $this->driver->reset($this->key());

        $this->fireEvent('meter.reset', new MeterReset(
            $this->name,
            $this->period,
            $this->key(),
        ));
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPeriod(): ?string
    {
        return $this->period;
    }

    public function getKey(): string
    {
        return $this->key();
    }

    /**
     * Build the storage key, appending the period window if set.
     */
    protected function key(): string
    {
        if ($this->period === null) {
            return $this->name;
        }

        return $this->name . ':' . $this->periodKey();
    }

    /**
     * Resolve the current period window for the meter.
     */
    protected function periodKey(): string
    {
        return match ($this->period) {
            'hourly' => date('Y-m-d-H'),
            'daily' => date('Y-m-d'),
            'weekly' => date('o-W'),
            'monthly' => date('Y-m'),
            'yearly' => date('Y'),
            default => throw new \InvalidArgumentException("Unsupported meter period: {$this->period}"),
        };
    }

    /**
     * Fire an event if an event dispatcher is available and the event is subscribed.
     */
    protected function fireEvent(string $event, object $data): void
    {
        if (! function_exists('app')) {
            return;
        }

        $container = app();

        if (! $container->has('event')) {
            return;
        }

        try {
            app('event')->fire($event, $data);
        } catch (\Lightpack\Exceptions\EventNotFoundException) {
            // No subscribers; ignore silently
        }
    }
}
