<?php

namespace Lightpack\Mail\Components;

class Section extends Component
{
    protected bool $isRoot = false;

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
     * Set background color
     */
    public function backgroundColor(string $color): self
    {
        $this->style('background-color', $color);
        return $this;
    }

    /**
     * Set as root section (includes HTML wrapper)
     */
    public function asRoot(): self
    {
        $this->isRoot = true;
        return $this;
    }

    /**
     * Render the section
     */
    public function render(): string
    {
        $styles = array_merge([
            'padding' => $this->padding . 'px',
        ], $this->styles);

        $styleAttr = $this->buildStyleAttribute($styles);
        $html = '';

        if ($this->isRoot) {
            $html .= '<!DOCTYPE html>' . PHP_EOL;
            $html .= '<html lang="en">' . PHP_EOL;
            $html .= '<head>' . PHP_EOL;
            $html .= '    <meta charset="UTF-8">' . PHP_EOL;
            $html .= '    <meta name="viewport" content="width=device-width, initial-scale=1.0">' . PHP_EOL;
            $html .= '    <meta http-equiv="X-UA-Compatible" content="IE=edge">' . PHP_EOL;
            $html .= '    <title>Welcome to Lightpack</title>' . PHP_EOL;
            $html .= '    <style>' . PHP_EOL;
            $html .= '        /* Reset styles */' . PHP_EOL;
            $html .= '        body, div, p, h1, h2, h3, h4, h5, h6 { margin: 0; padding: 0; }' . PHP_EOL;
            $html .= '        body { -webkit-font-smoothing: antialiased; }' . PHP_EOL;
            $html .= '        img { border: 0; display: block; }' . PHP_EOL;
            $html .= '        table { border-collapse: collapse; }' . PHP_EOL;
            $html .= '        ' . PHP_EOL;
            $html .= '        /* Base styles */' . PHP_EOL;
            $html .= '        body {' . PHP_EOL;
            $html .= '            font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif;' . PHP_EOL;
            $html .= '            font-size: 16px;' . PHP_EOL;
            $html .= '            line-height: 1.5;' . PHP_EOL;
            $html .= '            color: #333333;' . PHP_EOL;
            $html .= '            background-color: #f4f4f4;' . PHP_EOL;
            $html .= '        }' . PHP_EOL;
            $html .= '        ' . PHP_EOL;
            $html .= '        /* Container */' . PHP_EOL;
            $html .= '        .container {' . PHP_EOL;
            $html .= '            width: 100%;' . PHP_EOL;
            $html .= '            max-width: 600px;' . PHP_EOL;
            $html .= '            margin: 0 auto;' . PHP_EOL;
            $html .= '            background-color: #ffffff;' . PHP_EOL;
            $html .= '        }' . PHP_EOL;
            $html .= '        ' . PHP_EOL;
            $html .= '        /* Responsive */' . PHP_EOL;
            $html .= '        @media only screen and (max-width: 600px) {' . PHP_EOL;
            $html .= '            .container {' . PHP_EOL;
            $html .= '                width: 100% !important;' . PHP_EOL;
            $html .= '            }' . PHP_EOL;
            $html .= '        }' . PHP_EOL;
            $html .= '        ' . PHP_EOL;
            $html .= '        /* Dark mode */' . PHP_EOL;
            $html .= '        @media (prefers-color-scheme: dark) {' . PHP_EOL;
            $html .= '            body {' . PHP_EOL;
            $html .= '                background-color: #1a1a1a;' . PHP_EOL;
            $html .= '                color: #ffffff;' . PHP_EOL;
            $html .= '            }' . PHP_EOL;
            $html .= '            .container {' . PHP_EOL;
            $html .= '                background-color: #2d2d2d;' . PHP_EOL;
            $html .= '            }' . PHP_EOL;
            $html .= '        }' . PHP_EOL;
            $html .= '    </style>' . PHP_EOL;
            $html .= '</head>' . PHP_EOL;
            $html .= '<body>' . PHP_EOL;
            $html .= '    <table width="100%" cellpadding="0" cellspacing="0" border="0">' . PHP_EOL;
            $html .= '        <tr>' . PHP_EOL;
            $html .= '            <td align="center" style="padding: 40px 0;">' . PHP_EOL;
            $html .= '                <table class="container" width="600" cellpadding="0" cellspacing="0" border="0">' . PHP_EOL;
            $html .= '                    <tr><td>' . PHP_EOL;
        }

        $html .= "<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\"><tr><td {$styleAttr}>" . PHP_EOL;
        foreach ($this->children as $child) {
            $html .= $child->render() . PHP_EOL;
        }
        $html .= "</td></tr></table>" . PHP_EOL;

        if ($this->isRoot) {
            $html .= '                    </td></tr>' . PHP_EOL;
            $html .= '                </table>' . PHP_EOL;
            $html .= '            </td>' . PHP_EOL;
            $html .= '        </tr>' . PHP_EOL;
            $html .= '    </table>' . PHP_EOL;
            $html .= '</body>' . PHP_EOL;
            $html .= '</html>' . PHP_EOL;
        }

        return $html;
    }
}
