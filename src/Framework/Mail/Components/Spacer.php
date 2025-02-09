<?php

namespace Lightpack\Mail\Components;

class Spacer extends Component
{
    /**
     * Spacer height
     */
    protected int $height = 20;

    /**
     * Set spacer height
     */
    public function height(int $height): self
    {
        $this->height = $height;
        return $this;
    }

    /**
     * Render the spacer component
     */
    public function render(): string
    {
        return <<<HTML
        <div style="height: {$this->height}px;"></div>
        HTML;
    }
}
