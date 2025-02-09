<?php

namespace Lightpack\Mail\Components;

class Button extends Component
{
    /**
     * Default button styles
     */
    protected array $styles = [
        'display' => 'inline-block',
        'padding' => '12px 24px',
        'background-color' => '#007bff',
        'color' => '#ffffff',
        'text-decoration' => 'none',
        'border-radius' => '4px',
        'font-family' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif',
        'font-size' => '16px',
        'font-weight' => 'bold',
        'text-align' => 'center',
        'margin' => '0 0 16px 0',
    ];

    /**
     * Set button href
     */
    public function href(string $url): self
    {
        $this->attr('href', $url);
        return $this;
    }

    /**
     * Set button text color
     */
    public function color(string $color): self
    {
        $this->style('color', $color);
        return $this;
    }

    /**
     * Set button background color
     */
    public function backgroundColor(string $color): self
    {
        $this->style('background-color', $color);
        return $this;
    }

    /**
     * Set button size
     */
    public function size(string $padding): self
    {
        return $this->style('padding', $padding);
    }

    /**
     * Set button width
     */
    public function width(string $width): self
    {
        return $this->style('width', $width);
    }

    /**
     * Set button alignment
     */
    public function align(string $alignment): self
    {
        return $this->style('text-align', $alignment);
    }

    /**
     * Render the button component
     */
    public function render(): string
    {
        $styles = array_merge([
            'display' => 'inline-block',
            'padding' => '12px 24px',
            'color' => '#ffffff',
            'text-decoration' => 'none',
            'border-radius' => '4px',
            'font-family' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif',
            'font-size' => '16px',
            'font-weight' => 'bold',
            'text-align' => 'center',
            'margin' => '0 0 16px 0',
        ], $this->styles);

        $styleAttr = $this->buildStyleAttribute($styles);
        $attributes = $this->renderAttributes();

        // VML fallback for Outlook
        $html = '';
        $href = $this->attributes['href'] ?? '';
        if ($href) {
            $html .= '<!--[if mso]>' . PHP_EOL;
            $html .= '<v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="' . $href . '" style="height:40px;v-text-anchor:middle;width:200px;" arcsize="10%" stroke="f" fillcolor="' . ($this->styles['background-color'] ?? '#007bff') . '">' . PHP_EOL;
            $html .= '    <w:anchorlock/>' . PHP_EOL;
            $html .= '    <center>' . PHP_EOL;
            $html .= '<![endif]-->';
        }

        $html .= "<a{$attributes} {$styleAttr}>" . PHP_EOL;
        $html .= "    " . $this->content . PHP_EOL;
        $html .= "    " . PHP_EOL;
        $html .= "</a>" . PHP_EOL;

        if ($href) {
            $html .= '<!--[if mso]>' . PHP_EOL;
            $html .= '    </center>' . PHP_EOL;
            $html .= '</v:roundrect>' . PHP_EOL;
            $html .= '<![endif]-->';
        }

        return $html;
    }
}
