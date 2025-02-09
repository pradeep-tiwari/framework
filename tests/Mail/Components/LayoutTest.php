<?php

namespace Lightpack\Tests\Mail\Components;

use PHPUnit\Framework\TestCase;
use Lightpack\Mail\Components\Layout;
use Lightpack\Mail\Components\Text;

class LayoutTest extends TestCase
{
    private Layout $layout;

    protected function setUp(): void
    {
        $this->layout = new Layout();
    }

    public function testRendersBasicLayout()
    {
        $rendered = $this->layout
            ->content('Test Email')
            ->render();

        // Check for essential email template elements
        $this->assertStringContainsString('<!DOCTYPE html>', $rendered);
        $this->assertStringContainsString('<meta charset="UTF-8">', $rendered);
        $this->assertStringContainsString('<meta name="viewport"', $rendered);
        $this->assertStringContainsString('<title>Test Email</title>', $rendered);
        
        // Check for responsive design elements
        $this->assertStringContainsString('@media only screen and', $rendered);
        $this->assertStringContainsString('max-width: 600px', $rendered);
        
        // Check for dark mode support
        $this->assertStringContainsString('@media (prefers-color-scheme: dark)', $rendered);
    }

    public function testCanCustomizeEmailWidth()
    {
        $rendered = $this->layout
            ->width(800)
            ->render();

        $this->assertStringContainsString('max-width: 800px', $rendered);
        $this->assertStringContainsString('width="800"', $rendered);
    }

    public function testCanAddSections()
    {
        $rendered = $this->layout
            ->section('header', function($section) {
                $section->text('Header Content');
            })
            ->section('body', function($section) {
                $section->text('Body Content');
            })
            ->section('footer', function($section) {
                $section->text('Footer Content');
            })
            ->render();

        $this->assertStringContainsString('Header Content', $rendered);
        $this->assertStringContainsString('Body Content', $rendered);
        $this->assertStringContainsString('Footer Content', $rendered);
    }

    public function testSectionsHaveProperStructure()
    {
        $rendered = $this->layout
            ->section('body', function($section) {
                $section->text('Test');
            })
            ->render();

        // Check for table structure
        $this->assertStringContainsString('<table width="100%"', $rendered);
        $this->assertStringContainsString('<tr>', $rendered);
        $this->assertStringContainsString('<td', $rendered);
    }

    public function testIncludesResetStyles()
    {
        $rendered = $this->layout->render();

        $this->assertStringContainsString('margin: 0', $rendered);
        $this->assertStringContainsString('padding: 0', $rendered);
        $this->assertStringContainsString('border-collapse: collapse', $rendered);
    }

    public function testIncludesResponsiveStyles()
    {
        $rendered = $this->layout->render();

        $this->assertStringContainsString('@media only screen', $rendered);
        $this->assertStringContainsString('width: 100% !important', $rendered);
    }

    public function testIncludesDarkModeStyles()
    {
        $rendered = $this->layout->render();

        $this->assertStringContainsString('@media (prefers-color-scheme: dark)', $rendered);
        $this->assertStringContainsString('background-color: #1a1a1a', $rendered);
    }

    public function testCanCreateComplexEmail()
    {
        $rendered = $this->layout
            ->width(700)
            ->section('header', function($section) {
                $section->bold('Welcome')->size(24);
            })
            ->section('body', function($section) {
                $section
                    ->text('Hello World')
                    ->button('Click Me', 'https://example.com')
                    ->text('Thank you');
            })
            ->section('footer', function($section) {
                $section->text(' 2025')->align('center');
            })
            ->render();

        // Check structure
        $this->assertStringContainsString('width="700"', $rendered);
        $this->assertStringContainsString('Welcome', $rendered);
        $this->assertStringContainsString('Hello World', $rendered);
        $this->assertStringContainsString('Click Me', $rendered);
        $this->assertStringContainsString('Thank you', $rendered);
        $this->assertStringContainsString(' 2025', $rendered);
    }
}
