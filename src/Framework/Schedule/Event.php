<?php

namespace Lightpack\Schedule;

class Event
{
    private string $cronExpression;

    public function __construct(private string $type, private string $data)
    {
        // ...
    }

    public function setCron(string $expression): self
    {
        $this->cronExpression = $expression;

        return $this;
    }

    public function getCron(): string
    {
        return $this->cronExpression;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function isDue(): bool
    {
        return Cron::isDue($this->cronExpression);
    }
}
