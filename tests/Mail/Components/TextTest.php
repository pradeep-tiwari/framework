<?php

namespace Lightpack\Tests\Mail\Components;

use PHPUnit\Framework\TestCase;
use Lightpack\Mail\Components\Text;

class TextTest extends TestCase
{
    private Text $text;

    protected function setUp(): void
    {
        $this->text = new Text();
    }

    public function testRendersBasicText()
    {
        $this->text->content('Hello World');
        
        $rendered = $this->text->render();
        
        // Check for required styles
        $this->assertStringContainsString('margin: 0 0 16px 0', $rendered);
        $this->assertStringContainsString('font-family: -apple-system', $rendered);
        $this->assertStringContainsString('font-size: 16px', $rendered);
        $this->assertStringContainsString('line-height: 1.5', $rendered);
        $this->assertStringContainsString('color: #333333', $rendered);
        
        // Check content
        $this->assertStringContainsString('Hello World', $rendered);
    }

    public function testCanCustomizeTextSize()
    {
        $this->text
            ->content('Hello')
            ->size(20);

        $this->assertStringContainsString(
            'font-size: 20px',
            $this->text->render()
        );
    }

    public function testCanCustomizeTextColor()
    {
        $this->text
            ->content('Hello')
            ->color('#FF0000');

        $this->assertStringContainsString(
            'color: #FF0000',
            $this->text->render()
        );
    }

    public function testCanAlignText()
    {
        $this->text
            ->content('Hello')
            ->align('center');

        $this->assertStringContainsString(
            'text-align: center',
            $this->text->render()
        );
    }

    public function testCanMakeTextBold()
    {
        $this->text
            ->content('Hello')
            ->bold();

        $this->assertStringContainsString(
            'font-weight: bold',
            $this->text->render()
        );
    }

    public function testCanMakeTextItalic()
    {
        $this->text
            ->content('Hello')
            ->italic();

        $this->assertStringContainsString(
            'font-style: italic',
            $this->text->render()
        );
    }

    public function testCanCombineMultipleStyles()
    {
        $rendered = $this->text
            ->content('Hello')
            ->size(20)
            ->color('#FF0000')
            ->bold()
            ->align('center')
            ->render();

        $this->assertStringContainsString('font-size: 20px', $rendered);
        $this->assertStringContainsString('color: #FF0000', $rendered);
        $this->assertStringContainsString('font-weight: bold', $rendered);
        $this->assertStringContainsString('text-align: center', $rendered);
    }
}
