<?php

namespace Lightpack\Tests\Mail\Components;

use PHPUnit\Framework\TestCase;
use Lightpack\Mail\Components\Component;

class TestComponent extends Component
{
    public function render(): string
    {
        return sprintf(
            '<div%s%s>%s%s</div>',
            $this->renderAttributes(),
            $this->renderStyles(),
            $this->content,
            $this->renderChildren()
        );
    }
}

class ComponentTest extends TestCase
{
    private TestComponent $component;

    protected function setUp(): void
    {
        $this->component = new TestComponent();
    }

    public function testCanAddSingleAttribute()
    {
        $this->component->attr('id', 'test');
        $this->assertEquals(
            '<div id="test"></div>',
            $this->component->render()
        );
    }

    public function testCanAddMultipleAttributes()
    {
        $this->component->attrs([
            'id' => 'test',
            'class' => 'btn',
        ]);
        $this->assertEquals(
            '<div id="test" class="btn"></div>',
            $this->component->render()
        );
    }

    public function testCanAddSingleStyle()
    {
        $this->component->style('color', 'red');
        $this->assertEquals(
            '<div style="color: red;"></div>',
            $this->component->render()
        );
    }

    public function testCanAddMultipleStyles()
    {
        $this->component->styles([
            'color' => 'red',
            'font-size' => '16px',
        ]);
        $this->assertEquals(
            '<div style="color: red; font-size: 16px;"></div>',
            $this->component->render()
        );
    }

    public function testCanSetContent()
    {
        $this->component->content('Hello');
        $this->assertEquals(
            '<div>Hello</div>',
            $this->component->render()
        );
    }

    public function testCanAddChildComponent()
    {
        $child = new TestComponent();
        $child->content('Child');
        
        $this->component
            ->content('Parent')
            ->add($child);

        $this->assertEquals(
            '<div>Parent<div>Child</div></div>',
            $this->component->render()
        );
    }

    public function testAttributesAreProperlyEscaped()
    {
        $this->component->attr('data-text', 'Hello "World" & \'Friends\'');
        $this->assertEquals(
            '<div data-text="Hello &quot;World&quot; &amp; &#039;Friends&#039;"></div>',
            $this->component->render()
        );
    }

    public function testCanChainMethods()
    {
        $result = $this->component
            ->attr('id', 'test')
            ->style('color', 'red')
            ->content('Hello')
            ->render();

        $this->assertEquals(
            '<div id="test" style="color: red;">Hello</div>',
            $result
        );
    }
}
