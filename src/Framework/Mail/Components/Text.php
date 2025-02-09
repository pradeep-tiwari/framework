<?php

namespace Lightpack\Mail\Components;

class Text extends Component
{
    /**
     * Default text styles
     */
    protected array $styles = [
        'margin' => '0 0 16px 0',
        'font-family' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif',
        'font-size' => '16px',
        'line-height' => '1.5',
        'color' => '#333333',
    ];

    /**
     * Set text size
     */
    public function size(int $size): self
    {
        return $this->style('font-size', "{$size}px");
    }

    /**
     * Set text color
     */
    public function color(string $color): self
    {
        return $this->style('color', $color);
    }

    /**
     * Set text alignment
     */
    public function align(string $alignment): self
    {
        return $this->style('text-align', $alignment);
    }

    /**
     * Make text bold
     */
    public function bold(): self
    {
        return $this->style('font-weight', 'bold');
    }

    /**
     * Make text italic
     */
    public function italic(): self
    {
        return $this->style('font-style', 'italic');
    }

    /**
     * Render the text component
     */
    public function render(): string
    {
        return sprintf(
            '<p%s%s>%s%s</p>',
            $this->renderAttributes(),
            $this->renderStyles(),
            htmlspecialchars($this->content, ENT_QUOTES),
            $this->renderChildren()
        );
    }
}
