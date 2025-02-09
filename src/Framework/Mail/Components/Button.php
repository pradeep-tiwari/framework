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
     * Set button color
     */
    public function color(string $background, string $text = '#ffffff'): self
    {
        return $this->style('background-color', $background)
                    ->style('color', $text);
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
        // VML fallback for Outlook
        $backgroundColor = $this->styles['background-color'] ?? '#007bff';
        $href = $this->attributes['href'] ?? '#';
        
        return <<<HTML
        <!--[if mso]>
        <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="{$href}" style="height:40px;v-text-anchor:middle;width:200px;" arcsize="10%" stroke="f" fillcolor="{$backgroundColor}">
            <w:anchorlock/>
            <center>
        <![endif]-->
        <a{$this->renderAttributes()}{$this->renderStyles()}>
            {$this->content}
            {$this->renderChildren()}
        </a>
        <!--[if mso]>
            </center>
        </v:roundrect>
        <![endif]-->
        HTML;
    }
}
