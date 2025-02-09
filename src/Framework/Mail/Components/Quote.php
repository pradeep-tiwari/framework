<?php

namespace Lightpack\Mail\Components;

class Quote extends Component
{
    protected string $borderColor = '#007bff';
    protected string $backgroundColor = '#f8f9fa';
    protected string $textColor = '#333333';
    protected string $author = '';
    protected string $role = '';

    /**
     * Set quote border color
     */
    public function borderColor(string $color): self
    {
        $this->borderColor = $color;
        return $this;
    }

    /**
     * Set quote background color
     */
    public function backgroundColor(string $color): self
    {
        $this->backgroundColor = $color;
        return $this;
    }

    /**
     * Set quote text color
     */
    public function textColor(string $color): self
    {
        $this->textColor = $color;
        return $this;
    }

    /**
     * Set quote author
     */
    public function author(string $name, string $role = ''): self
    {
        $this->author = $name;
        $this->role = $role;
        return $this;
    }

    /**
     * Render the quote component
     */
    public function render(): string
    {
        if (empty($this->content)) {
            return '';
        }

        $styles = array_merge([
            'background-color' => $this->backgroundColor,
            'border-left' => "4px solid {$this->borderColor}",
            'color' => $this->textColor,
            'padding' => '20px',
            'margin' => '0 0 16px 0',
            'font-style' => 'italic',
        ], $this->styles);

        $styleAttr = $this->buildStyleAttribute($styles);

        $html = "<blockquote {$styleAttr}>" . PHP_EOL;
        $html .= "<p style=\"margin: 0 0 16px 0;\">{$this->content}</p>" . PHP_EOL;

        if ($this->author) {
            $html .= "<footer style=\"font-size: 14px; font-style: normal;\">" . PHP_EOL;
            $html .= "— <strong>{$this->author}</strong>";
            if ($this->role) {
                $html .= "<br><span style=\"color: #6c757d;\">{$this->role}</span>";
            }
            $html .= PHP_EOL . "</footer>";
        }

        $html .= "</blockquote>";

        return $html;
    }
}
