<?php

namespace Lightpack\Mail\Components;

class Divider extends Component
{
    /**
     * Default divider styles
     */
    protected array $styles = [
        'border' => 'none',
        'border-top' => '1px solid #e0e0e0',
        'margin' => '20px 0',
        'width' => '100%',
    ];

    /**
     * Set divider color
     */
    public function color(string $color): self
    {
        return $this->style('border-top-color', $color);
    }

    /**
     * Set divider style (solid, dashed, dotted)
     */
    public function lineStyle(string $style): self
    {
        return $this->style('border-top-style', $style);
    }

    /**
     * Set divider width
     */
    public function width(string $width): self
    {
        return $this->style('width', $width);
    }

    /**
     * Render the divider component
     */
    public function render(): string
    {
        return <<<HTML
        <hr{$this->renderAttributes()}{$this->renderStyles()}>
        {$this->renderChildren()}
        HTML;
    }
}
