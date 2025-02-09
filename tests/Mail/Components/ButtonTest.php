<?php

namespace Lightpack\Tests\Mail\Components;

use PHPUnit\Framework\TestCase;
use Lightpack\Mail\Components\Button;

class ButtonTest extends TestCase
{
    private Button $button;

    protected function setUp(): void
    {
        $this->button = new Button();
    }

    public function testRendersBasicButton()
    {
        $rendered = $this->button
            ->content('Click Me')
            ->attr('href', 'https://example.com')
            ->render();

        // Check for VML fallback
        $this->assertStringContainsString('<!--[if mso]>', $rendered);
        $this->assertStringContainsString('<v:roundrect', $rendered);
        
        // Check for actual button
        $this->assertStringContainsString('<a href="https://example.com"', $rendered);
        $this->assertStringContainsString('Click Me', $rendered);
        
        // Check default styles
        $this->assertStringContainsString('display: inline-block', $rendered);
        $this->assertStringContainsString('background-color: #007bff', $rendered);
        $this->assertStringContainsString('color: #ffffff', $rendered);
    }

    public function testCanCustomizeButtonColors()
    {
        $rendered = $this->button
            ->content('Click Me')
            ->color('#FF0000', '#000000')
            ->render();

        $this->assertStringContainsString('background-color: #FF0000', $rendered);
        $this->assertStringContainsString('color: #000000', $rendered);
        
        // Check VML color
        $this->assertStringContainsString('fillcolor="#FF0000"', $rendered);
    }

    public function testCanCustomizeButtonSize()
    {
        $rendered = $this->button
            ->content('Click Me')
            ->size('16px 32px')
            ->render();

        $this->assertStringContainsString('padding: 16px 32px', $rendered);
    }

    public function testCanSetButtonWidth()
    {
        $rendered = $this->button
            ->content('Click Me')
            ->width('300px')
            ->render();

        $this->assertStringContainsString('width: 300px', $rendered);
    }

    public function testCanAlignButton()
    {
        $rendered = $this->button
            ->content('Click Me')
            ->align('center')
            ->render();

        $this->assertStringContainsString('text-align: center', $rendered);
    }

    public function testEscapesUrlProperly()
    {
        $rendered = $this->button
            ->content('Click Me')
            ->attr('href', 'https://example.com?name=John&age=30')
            ->render();

        $this->assertStringContainsString(
            'href="https://example.com?name=John&amp;age=30"',
            $rendered
        );
    }

    public function testCanCombineMultipleCustomizations()
    {
        $rendered = $this->button
            ->content('Click Me')
            ->color('#FF0000', '#000000')
            ->size('16px 32px')
            ->width('300px')
            ->align('center')
            ->render();

        $this->assertStringContainsString('background-color: #FF0000', $rendered);
        $this->assertStringContainsString('color: #000000', $rendered);
        $this->assertStringContainsString('padding: 16px 32px', $rendered);
        $this->assertStringContainsString('width: 300px', $rendered);
        $this->assertStringContainsString('text-align: center', $rendered);
    }
}
