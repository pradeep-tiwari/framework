<?php

namespace Lightpack\Mail\Components;

class ListComponent extends Component
{
    protected array $items = [];
    protected string $type = 'ul';
    protected string $bulletColor = '#333333';
    protected int $indent = 20;

    /**
     * Set list type (ul or ol)
     */
    public function type(string $type): self
    {
        $this->type = in_array($type, ['ul', 'ol']) ? $type : 'ul';
        return $this;
    }

    /**
     * Add a list item
     */
    public function item(string $content): self
    {
        $this->items[] = $content;
        return $this;
    }

    /**
     * Add multiple items at once
     */
    public function items(array $items): self
    {
        foreach ($items as $item) {
            $this->item($item);
        }
        return $this;
    }

    /**
     * Set bullet color for unordered lists
     */
    public function bulletColor(string $color): self
    {
        $this->bulletColor = $color;
        return $this;
    }

    /**
     * Set list indent
     */
    public function indent(int $pixels): self
    {
        $this->indent = $pixels;
        return $this;
    }

    /**
     * Render the list component
     */
    public function render(): string
    {
        if (empty($this->items)) {
            return '';
        }

        $styles = array_merge([
            'margin' => "0 0 16px {$this->indent}px",
            'padding' => '0',
            'list-style-position' => 'outside',
            'text-indent' => '-1em',
            'padding-left' => '1em',
        ], $this->styles);

        if ($this->type === 'ul') {
            $styles['list-style'] = 'disc';
            $styles['color'] = $this->bulletColor;
        }

        $styleAttr = $this->buildStyleAttribute($styles);
        $items = array_map(function($item) {
            return "<li style=\"margin: 8px 0; color: inherit;\"><span style=\"color: #333333;\">{$item}</span></li>";
        }, $this->items);

        return "<{$this->type} {$styleAttr}>" . PHP_EOL . 
               implode(PHP_EOL, $items) . PHP_EOL .
               "</{$this->type}>";
    }
}
