<?php

namespace Lightpack\Mail\Components;

class Section extends Component
{
    /**
     * Section padding
     */
    protected int $padding = 40;

    /**
     * Set section padding
     */
    public function padding(int $padding): self
    {
        $this->padding = $padding;
        return $this;
    }

    /**
     * Add text component
     */
    public function text(string $content): self
    {
        return $this->add((new Text)->content($content));
    }

    /**
     * Add text component with bold style
     */
    public function bold(string $content): Text
    {
        $text = new Text;
        $text->content($content)->bold();
        $this->add($text);
        return $text;
    }

    /**
     * Add text component with specific size
     */
    public function size(int $size): self
    {
        if ($this->children) {
            $lastChild = end($this->children);
            if ($lastChild instanceof Text) {
                $lastChild->size($size);
            }
        }
        return $this;
    }

    /**
     * Add text component with specific color
     */
    public function color(string $color): self
    {
        if ($this->children) {
            $lastChild = end($this->children);
            if ($lastChild instanceof Text) {
                $lastChild->color($color);
            }
        }
        return $this;
    }

    /**
     * Add text component with specific alignment
     */
    public function align(string $alignment): self
    {
        if ($this->children) {
            $lastChild = end($this->children);
            if ($lastChild instanceof Text) {
                $lastChild->align($alignment);
            }
        }
        return $this;
    }

    /**
     * Add button component
     */
    public function button(string $text, string $url): self
    {
        return $this->add((new Button)->content($text)->attr('href', $url));
    }

    /**
     * Add image component
     */
    public function image(string $src, string $alt = ''): self
    {
        return $this->add((new Image)->attr('src', $src)->attr('alt', $alt));
    }

    /**
     * Add spacer
     */
    public function spacer(int $height): self
    {
        return $this->add((new Spacer)->height($height));
    }

    /**
     * Add divider
     */
    public function divider(): self
    {
        return $this->add(new Divider);
    }

    /**
     * Add link
     */
    public function link(string $text, string $url): self
    {
        return $this->add((new Link)->content($text)->attr('href', $url));
    }

    /**
     * Render the section
     */
    public function render(): string
    {
        $this->style('padding', "{$this->padding}px");

        return <<<HTML
        <tr>
            <td{$this->renderAttributes()}{$this->renderStyles()}>
                {$this->content}
                {$this->renderChildren()}
            </td>
        </tr>
        HTML;
    }
}
