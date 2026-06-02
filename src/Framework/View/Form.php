<?php

namespace Lightpack\View;

class Form
{
    protected array $config = [];

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'wrapper' => [],
            'label' => [],
            'input' => [],
            'error' => [],
        ], $config);
    }

    /**
     * Set configuration for this Form instance.
     * Returns self for chaining.
     */
    public function configure(array $config): self
    {
        $this->config = array_merge($this->config, $config);

        return $this;
    }

    /**
     * Open a form tag with CSRF and method spoofing support.
     */
    public function open(string $action = '', string $method = 'POST', array $attrs = [], bool $csrf = true): string
    {
        return form_open($action, $method, $attrs, $csrf);
    }

    /**
     * Close a form tag.
     */
    public function close(): string
    {
        return form_close();
    }

    /**
     * Render a text input field.
     */
    public function text(string $name, string $label, array $attrs = []): string
    {
        return $this->buildField($name, $label, 'text', $attrs);
    }

    /**
     * Render an email input field.
     */
    public function email(string $name, string $label, array $attrs = []): string
    {
        return $this->buildField($name, $label, 'email', $attrs);
    }

    /**
     * Render a password input field.
     * Old input values are never repopulated for security.
     */
    public function password(string $name, string $label, array $attrs = []): string
    {
        return $this->buildField($name, $label, 'password', $attrs, null, false);
    }

    /**
     * Render a textarea field.
     */
    public function textarea(string $name, string $label, array $attrs = []): string
    {
        return $this->buildField($name, $label, 'textarea', $attrs);
    }

    /**
     * Render a select dropdown field.
     */
    public function select(string $name, string $label, array $options, array $attrs = []): string
    {
        return $this->buildField($name, $label, 'select', $attrs, $options);
    }

    /**
     * Render a checkbox field.
     */
    public function checkbox(string $name, string $label, array $attrs = []): string
    {
        return $this->buildField($name, $label, 'checkbox', $attrs);
    }

    /**
     * Render a radio button field.
     */
    public function radio(string $name, string $label, string $value, array $attrs = []): string
    {
        return $this->buildField($name, $label, 'radio', array_merge($attrs, ['value' => $value]));
    }

    /**
     * Render a file upload field.
     * Old input values are never repopulated for security.
     */
    public function file(string $name, string $label, array $attrs = []): string
    {
        return $this->buildField($name, $label, 'file', $attrs, null, false);
    }

    /**
     * Render a hidden input field.
     */
    public function hidden(string $name, ?string $value = null, array $attrs = []): string
    {
        $attrs['type'] = 'hidden';
        $attrs['name'] = $name;
        $attrs['id'] = $this->makeId($name);

        if ($value !== null) {
            $attrs['value'] = $value;
        }

        return '<input' . $this->renderAttributes($attrs) . '>';
    }

    /**
     * Render a submit button.
     */
    public function submit(string $text, array $attrs = []): string
    {
        $attrs['type'] = 'submit';

        return '<button' . $this->renderAttributes($attrs) . '>' . _e($text) . '</button>';
    }

    /**
     * Render a label tag.
     */
    public function label(string $name, string $text, array $attrs = []): string
    {
        $attrs = array_merge($this->config['label'], $attrs);
        $attrs['for'] = $this->makeId($name);

        return '<label' . $this->renderAttributes($attrs) . '>' . _e($text) . '</label>';
    }

    /**
     * Render a bare input tag.
     */
    public function input(string $name, string $type = 'text', array $attrs = []): string
    {
        $attrs['type'] = $type;
        $attrs['name'] = $name;
        $attrs['id'] = $this->makeId($name);

        if (! isset($attrs['value']) && $type !== 'password' && $type !== 'file') {
            $attrs['value'] = old($name, '', false);
        }

        $attrs = array_merge($this->config['input'], $attrs);

        return '<input' . $this->renderAttributes($attrs) . '>';
    }

    /**
     * Render an error message for a field.
     */
    public function error(string $name): string
    {
        $message = error($name);

        if (empty($message)) {
            return '';
        }

        $attrs = array_merge($this->config['error'], []);

        return '<span' . $this->renderAttributes($attrs) . '>' . _e($message) . '</span>';
    }

    /**
     * Build a complete field group (wrapper + label + input + error).
     */
    protected function buildField(
        string $name,
        string $label,
        string $type,
        array $attrs = [],
        ?array $options = null,
        bool $repopulate = true
    ): string {
        $html = '';

        // Wrapper open
        $wrapperAttrs = array_merge($this->config['wrapper'], []);
        $html .= '<div' . $this->renderAttributes($wrapperAttrs) . '>' . "\n";

        // Label
        $html .= '    ' . $this->label($name, $label) . "\n";

        // Input
        if ($type === 'textarea') {
            $html .= '    ' . $this->buildTextarea($name, $attrs) . "\n";
        } elseif ($type === 'select') {
            $html .= '    ' . $this->buildSelect($name, $options ?? [], $attrs) . "\n";
        } elseif ($type === 'checkbox') {
            $html .= '    ' . $this->buildCheckbox($name, $attrs) . "\n";
        } elseif ($type === 'radio') {
            $html .= '    ' . $this->buildRadio($name, $attrs) . "\n";
        } else {
            $inputAttrs = array_merge($this->config['input'], $attrs);
            $inputAttrs['type'] = $type;
            $inputAttrs['name'] = $name;
            $inputAttrs['id'] = $this->makeId($name);

            if ($repopulate && ! isset($inputAttrs['value'])) {
                $inputAttrs['value'] = old($name, '', false);
            }

            $html .= '    <input' . $this->renderAttributes($inputAttrs) . '>' . "\n";
        }

        // Error
        $errorHtml = $this->error($name);
        if (! empty($errorHtml)) {
            $html .= '    ' . $errorHtml . "\n";
        }

        // Wrapper close
        $html .= '</div>';

        return $html;
    }

    /**
     * Build a textarea element.
     */
    protected function buildTextarea(string $name, array $attrs): string
    {
        $attrs = array_merge($this->config['input'], $attrs);
        $attrs['name'] = $name;
        $attrs['id'] = $this->makeId($name);

        $value = old($name, '', false);

        return '<textarea' . $this->renderAttributes($attrs) . '>' . _e($value) . '</textarea>';
    }

    /**
     * Build a select element with options.
     */
    protected function buildSelect(string $name, array $options, array $attrs): string
    {
        $attrs = array_merge($this->config['input'], $attrs);
        $attrs['name'] = $name;
        $attrs['id'] = $this->makeId($name);

        $selectedValue = old($name, '', false);

        $html = '<select' . $this->renderAttributes($attrs) . '>' . "\n";

        foreach ($options as $value => $label) {
            $isSelected = (string) $selectedValue === (string) $value;
            $selectedAttr = $isSelected ? ' selected' : '';
            $html .= '        <option value="' . _e((string) $value) . '"' . $selectedAttr . '>' . _e((string) $label) . '</option>' . "\n";
        }

        $html .= '    </select>';

        return $html;
    }

    /**
     * Build a checkbox input.
     */
    protected function buildCheckbox(string $name, array $attrs): string
    {
        $attrs = array_merge($this->config['input'], $attrs);
        $attrs['type'] = 'checkbox';
        $attrs['name'] = $name;
        $attrs['id'] = $this->makeId($name);
        $attrs['value'] = $attrs['value'] ?? '1';

        $oldValue = old($name, '', false);
        if ($oldValue !== '' && $oldValue !== null) {
            $attrs['checked'] = 'checked';
        }

        return '<input' . $this->renderAttributes($attrs) . '>';
    }

    /**
     * Build a radio input.
     */
    protected function buildRadio(string $name, array $attrs): string
    {
        $value = $attrs['value'] ?? '';
        unset($attrs['value']);

        $attrs = array_merge($this->config['input'], $attrs);
        $attrs['type'] = 'radio';
        $attrs['name'] = $name;
        $attrs['id'] = $this->makeId($name . '-' . $value);
        $attrs['value'] = $value;

        $oldValue = old($name, '', false);
        if ((string) $oldValue === (string) $value) {
            $attrs['checked'] = 'checked';
        }

        return '<input' . $this->renderAttributes($attrs) . '>';
    }

    /**
     * Render HTML attributes from an associative array.
     */
    protected function renderAttributes(array $attrs): string
    {
        $html = '';

        foreach ($attrs as $key => $value) {
            if ($value === null || $value === false) {
                continue;
            }

            if ($value === true) {
                $html .= ' ' . _e($key);
                continue;
            }

            $html .= ' ' . _e((string) $key) . '="' . _e((string) $value) . '"';
        }

        return $html;
    }

    /**
     * Generate a valid HTML ID from a field name.
     */
    protected function makeId(string $name): string
    {
        $id = preg_replace('/[^a-zA-Z0-9_-]/', '-', $name);
        $id = preg_replace('/-+/', '-', $id);
        return trim($id, '-');
    }
}
