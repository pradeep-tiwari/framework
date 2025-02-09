<?php

namespace Lightpack\Mail\Components;

class Card extends Component
{
    protected string $backgroundColor = '#ffffff';
    protected string $borderColor = '#e0e0e0';
    protected int $borderRadius = 8;
    protected int $padding = 24;
    protected string $title = '';
    protected string $cardContent = '';

    /**
     * Set card background color
     */
    public function backgroundColor(string $color): self
    {
        $this->backgroundColor = $color;
        return $this;
    }

    /**
     * Set card border color
     */
    public function borderColor(string $color): self
    {
        $this->borderColor = $color;
        return $this;
    }

    /**
     * Set card border radius
     */
    public function borderRadius(int $pixels): self
    {
        $this->borderRadius = $pixels;
        return $this;
    }

    /**
     * Set card padding
     */
    public function padding(int $pixels): self
    {
        $this->padding = $pixels;
        return $this;
    }

    /**
     * Set card title
     */
    public function title(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Set card content
     */
    public function content(string $content): self
    {
        $this->cardContent = $content;
        return $this;
    }

    /**
     * Render the card component
     */
    public function render(): string
    {
        $styles = array_merge([
            'background-color' => $this->backgroundColor,
            'border' => "1px solid {$this->borderColor}",
            'border-radius' => "{$this->borderRadius}px",
            'padding' => "{$this->padding}px",
            'margin' => '0 0 16px 0',
            // Email-safe box shadow
            'box-shadow' => '0 2px 4px rgba(0, 0, 0, 0.1)',
            '-webkit-box-shadow' => '0 2px 4px rgba(0, 0, 0, 0.1)',
            '-moz-box-shadow' => '0 2px 4px rgba(0, 0, 0, 0.1)',
        ], $this->styles);

        $styleAttr = $this->buildStyleAttribute($styles);

        $html = "<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\">" . PHP_EOL .
               "<tr><td {$styleAttr}>" . PHP_EOL;
        
        if ($this->title) {
            $html .= "<p style=\"margin: 0 0 16px 0; font-family: -apple-system, BlinkMacSystemFont, &quot;Segoe UI&quot;, Roboto, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 1.5; color: #333333; font-weight: bold;\">{$this->title}</p>";
        }

        if ($this->cardContent) {
            $html .= "<p style=\"margin: 0 0 16px 0; font-family: -apple-system, BlinkMacSystemFont, &quot;Segoe UI&quot;, Roboto, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 1.5; color: #333333;\">{$this->cardContent}</p>";
        }

        $html .= $this->renderChildren() . PHP_EOL .
               "</td></tr>" . PHP_EOL .
               "</table>";

        return $html;
    }
}
