<?php

namespace Lightpack\Mail\Components;

abstract class Component
{
    /**
     * Component attributes
     */
    protected array $attributes = [];

    /**
     * Component styles
     */
    protected array $styles = [];

    /**
     * Component content
     */
    protected string $content = '';

    /**
     * Child components
     */
    protected array $children = [];

    /**
     * Add an attribute to the component
     */
    public function attr(string $name, string $value): self
    {
        $this->attributes[$name] = $value;
        return $this;
    }

    /**
     * Add multiple attributes
     */
    public function attrs(array $attributes): self
    {
        foreach ($attributes as $name => $value) {
            $this->attr($name, $value);
        }
        return $this;
    }

    /**
     * Add a style to the component
     */
    public function style(string $property, string $value): self
    {
        $this->styles[$property] = $value;
        return $this;
    }

    /**
     * Add multiple styles
     */
    public function styles(array $styles): self
    {
        foreach ($styles as $property => $value) {
            $this->style($property, $value);
        }
        return $this;
    }

    /**
     * Set component content
     */
    public function content(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Add a child component
     */
    public function add(Component $component): self
    {
        $this->children[] = $component;
        return $this;
    }

    /**
     * Get rendered attributes string
     */
    protected function renderAttributes(): string
    {
        if (empty($this->attributes)) {
            return '';
        }

        $attributes = [];
        foreach ($this->attributes as $name => $value) {
            $attributes[] = sprintf('%s="%s"', $name, htmlspecialchars($value, ENT_QUOTES));
        }

        return ' ' . implode(' ', $attributes);
    }

    /**
     * Get rendered styles string
     */
    protected function renderStyles(): string
    {
        if (empty($this->styles)) {
            return '';
        }

        $styles = [];
        foreach ($this->styles as $property => $value) {
            $styles[] = sprintf('%s: %s;', $property, $value);
        }

        return ' style="' . implode(' ', $styles) . '"';
    }

    /**
     * Build the style attribute string
     */
    protected function buildStyleAttribute(array $styles = []): string
    {
        $allStyles = array_merge($this->styles, $styles);
        if (empty($allStyles)) {
            return '';
        }

        $styleStr = array_map(
            fn($name, $value) => "{$name}: {$value};",
            array_keys($allStyles),
            array_values($allStyles)
        );

        return 'style="' . implode(' ', $styleStr) . '"';
    }

    /**
     * Get rendered children
     */
    protected function renderChildren(): string
    {
        return implode('', array_map(fn(Component $child) => $child->render(), $this->children));
    }

    /**
     * Render the component
     */
    abstract public function render(): string;
}
