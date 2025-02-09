<?php

namespace Lightpack\Mail\Components;

class Link extends Component
{
    /**
     * Default link styles
     */
    protected array $styles = [
        'color' => '#007bff',
        'text-decoration' => 'underline',
    ];

    /**
     * Set link color
     */
    public function color(string $color): self
    {
        return $this->style('color', $color);
    }

    /**
     * Remove underline
     */
    public function noUnderline(): self
    {
        return $this->style('text-decoration', 'none');
    }

    /**
     * Set link as bold
     */
    public function bold(): self
    {
        return $this->style('font-weight', 'bold');
    }

    /**
     * Render the link component
     */
    public function render(): string
    {
        return <<<HTML
        <a{$this->renderAttributes()}{$this->renderStyles()}>
            {$this->content}
            {$this->renderChildren()}
        </a>
        HTML;
    }
}
