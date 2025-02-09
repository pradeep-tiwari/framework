<?php

namespace Lightpack\Mail\Components;

class Image extends Component
{
    /**
     * Default image styles
     */
    protected array $styles = [
        'max-width' => '100%',
        'height' => 'auto',
        'display' => 'block',
        'margin' => '0 0 16px 0',
    ];

    /**
     * Set image source
     */
    public function src(string $url): self
    {
        $this->attr('src', $url);
        return $this;
    }

    /**
     * Set image alt text
     */
    public function alt(string $text): self
    {
        $this->attr('alt', $text);
        return $this;
    }

    /**
     * Set image width
     */
    public function width(string $width): self
    {
        return $this->style('width', $width);
    }

    /**
     * Set image height
     */
    public function height(string $height): self
    {
        return $this->style('height', $height);
    }

    /**
     * Set image alignment
     */
    public function align(string $alignment): self
    {
        return $this->style('margin', $alignment === 'center' ? '0 auto 16px' : '0 0 16px 0');
    }

    /**
     * Render the image component
     */
    public function render(): string
    {
        $styles = array_merge([
            'max-width' => '100%',
            'height' => 'auto',
            'display' => 'block',
            'margin' => '0 0 16px 0',
        ], $this->styles);

        $styleAttr = $this->buildStyleAttribute($styles);
        $attributes = $this->renderAttributes();

        return "<img{$attributes} {$styleAttr}>";
    }
}
