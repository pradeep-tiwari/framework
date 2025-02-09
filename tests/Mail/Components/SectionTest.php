<?php

namespace Lightpack\Tests\Mail\Components;

use PHPUnit\Framework\TestCase;
use Lightpack\Mail\Components\Section;

class SectionTest extends TestCase
{
    private Section $section;

    protected function setUp(): void
    {
        $this->section = new Section();
    }

    public function testRendersBasicSection()
    {
        $rendered = $this->section->render();

        $this->assertStringContainsString('<tr>', $rendered);
        $this->assertStringContainsString('<td', $rendered);
        $this->assertStringContainsString('padding: 40px', $rendered);
    }

    public function testCanCustomizePadding()
    {
        $rendered = $this->section
            ->padding(20)
            ->render();

        $this->assertStringContainsString('padding: 20px', $rendered);
    }

    public function testCanAddText()
    {
        $rendered = $this->section
            ->text('Hello World')
            ->render();

        $this->assertStringContainsString('Hello World', $rendered);
        $this->assertStringContainsString('<p', $rendered);
    }

    public function testCanAddButton()
    {
        $rendered = $this->section
            ->button('Click Me', 'https://example.com')
            ->render();

        $this->assertStringContainsString('Click Me', $rendered);
        $this->assertStringContainsString('href="https://example.com"', $rendered);
    }

    public function testCanAddImage()
    {
        $rendered = $this->section
            ->image('image.jpg', 'Test Image')
            ->render();

        $this->assertStringContainsString('src="image.jpg"', $rendered);
        $this->assertStringContainsString('alt="Test Image"', $rendered);
    }

    public function testCanAddSpacer()
    {
        $rendered = $this->section
            ->spacer(20)
            ->render();

        $this->assertStringContainsString('height: 20px', $rendered);
    }

    public function testCanAddDivider()
    {
        $rendered = $this->section
            ->divider()
            ->render();

        $this->assertStringContainsString('<hr', $rendered);
    }

    public function testCanAddLink()
    {
        $rendered = $this->section
            ->link('Click Here', 'https://example.com')
            ->render();

        $this->assertStringContainsString('Click Here', $rendered);
        $this->assertStringContainsString('href="https://example.com"', $rendered);
    }

    public function testCanCombineMultipleComponents()
    {
        $rendered = $this->section
            ->text('Hello')
            ->spacer(20)
            ->button('Click Me', 'https://example.com')
            ->spacer(20)
            ->text('Goodbye')
            ->render();

        $this->assertStringContainsString('Hello', $rendered);
        $this->assertStringContainsString('height: 20px', $rendered);
        $this->assertStringContainsString('Click Me', $rendered);
        $this->assertStringContainsString('Goodbye', $rendered);
    }

    public function testCanCustomizeSectionStyles()
    {
        $rendered = $this->section
            ->style('background-color', '#f0f0f0')
            ->style('border-bottom', '1px solid #ddd')
            ->render();

        $this->assertStringContainsString('background-color: #f0f0f0', $rendered);
        $this->assertStringContainsString('border-bottom: 1px solid #ddd', $rendered);
    }
}
