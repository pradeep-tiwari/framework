<?php

namespace Lightpack\Mail\Components;

class Layout extends Component
{
    /**
     * Default email width
     */
    protected int $width = 600;

    /**
     * Layout sections
     */
    protected array $sections = [
        'header' => null,
        'body' => null,
        'footer' => null,
    ];

    /**
     * Set email width
     */
    public function width(int $width): self
    {
        $this->width = $width;
        return $this;
    }

    /**
     * Add a section with callback configuration
     */
    public function section(string $name, callable $callback): self
    {
        $section = new Section();
        $callback($section);
        $this->sections[$name] = $section;
        return $this;
    }

    /**
     * Render the email layout
     */
    public function render(): string
    {
        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <title>{$this->content}</title>
            <style>
                /* Reset styles */
                body, div, p, h1, h2, h3, h4, h5, h6 { margin: 0; padding: 0; }
                body { -webkit-font-smoothing: antialiased; }
                img { border: 0; display: block; }
                table { border-collapse: collapse; }
                
                /* Base styles */
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
                    font-size: 16px;
                    line-height: 1.5;
                    color: #333333;
                    background-color: #f4f4f4;
                }
                
                /* Container */
                .container {
                    width: 100%;
                    max-width: {$this->width}px;
                    margin: 0 auto;
                    background-color: #ffffff;
                }
                
                /* Responsive */
                @media only screen and (max-width: {$this->width}px) {
                    .container {
                        width: 100% !important;
                    }
                }
                
                /* Dark mode */
                @media (prefers-color-scheme: dark) {
                    body {
                        background-color: #1a1a1a;
                        color: #ffffff;
                    }
                    .container {
                        background-color: #2d2d2d;
                    }
                }
            </style>
        </head>
        <body>
            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td align="center" style="padding: 40px 0;">
                        <table class="container" width="{$this->width}" cellpadding="0" cellspacing="0" border="0">
                            {$this->renderSections()}
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        HTML;
    }

    /**
     * Render all sections
     */
    protected function renderSections(): string
    {
        $output = '';
        foreach ($this->sections as $name => $section) {
            if ($section) {
                $output .= $section->render();
            }
        }
        return $output;
    }
}
