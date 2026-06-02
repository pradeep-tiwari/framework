# Form

Lightpack's `Form` class generates HTML form fields with zero boilerplate. It auto-wires **CSRF tokens**, **validation error messages**, and **old input repopulation** — the three things every form needs and every developer writes over and over again.

---

## Quick Start

```php
<?php $f = form() ?>
<?= $f->open('/users', 'POST') ?>

<?= $f->text('name', 'Full Name') ?>
<?= $f->email('email', 'Email Address') ?>
<?= $f->password('password', 'Password') ?>
<?= $f->submit('Create Account') ?>

<?= $f->close() ?>
```

That's it. The `name` field repopulates after a validation error. The `email` field shows its error inline. The CSRF token is injected automatically.

---

## Form Tag

### Open

```php
form()->open(string $action = '', string $method = 'POST', array $attrs = [], bool $csrf = true): string
```

```php
<?php $f = form() ?>
<?= $f->open('/users') ?>
<?= $f->open('/users/1', 'PUT') ?>
<?= $f->open('/search', 'GET', ['class' => 'search-form'], false) ?>
```

| Method | Actual `method` | Hidden `_method` |
|---|---|---|
| `GET` | `GET` | No |
| `POST` | `POST` | No |
| `PUT` | `POST` | Yes |
| `PATCH` | `POST` | Yes |
| `DELETE` | `POST` | Yes |

CSRF token is injected for all state-changing methods (`POST`, `PUT`, `PATCH`, `DELETE`).

### Close

```php
form()->close(): string
```

Returns `</form>`.

---

## Field Methods

Every field method returns a single string containing a `<div>` wrapper, a `<label>`, an `<input>` (or equivalent), and optionally an error `<span>`.

All methods are called on the same `Form` instance:

```php
<?php $f = form() ?>
```

| Method | Generates |
|---|---|
| `$f->text($name, $label, $attrs = [])` | `<input type="text">` |
| `$f->email($name, $label, $attrs = [])` | `<input type="email">` |
| `$f->password($name, $label, $attrs = [])` | `<input type="password">` (never repopulated) |
| `$f->textarea($name, $label, $attrs = [])` | `<textarea>` |
| `$f->select($name, $label, $options, $attrs = [])` | `<select>` with `<option>` tags |
| `$f->checkbox($name, $label, $attrs = [])` | `<input type="checkbox">` |
| `$f->radio($name, $label, $value, $attrs = [])` | `<input type="radio">` |
| `$f->file($name, $label, $attrs = [])` | `<input type="file">` (never repopulated) |
| `$f->hidden($name, $value = null, $attrs = [])` | `<input type="hidden">` |
| `$f->submit($text, $attrs = [])` | `<button type="submit">` |

### Example: Complete Form

```php
<?php $f = form() ?>
<?= $f->open('/posts', 'POST') ?>

<?= $f->text('title', 'Title') ?>
<?= $f->textarea('body', 'Content') ?>
<?= $f->select('category', 'Category', [
    'tech' => 'Technology',
    'life' => 'Lifestyle',
]) ?>
<?= $f->checkbox('featured', 'Mark as featured') ?>
<?= $f->file('cover', 'Cover Image') ?>
<?= $f->hidden('author_id', auth()->id()) ?>
<?= $f->submit('Publish') ?>

<?= $f->close() ?>
```

---

## Old Input Repopulation

All fields automatically pull from `old()` (the `_old_input` flash data set by `FormRequest` on validation failure).

```php
<?php $f = form() ?>

// User typed "John" and submitted. Validation failed on another field.
// The form comes back with "John" still in the name field.
<?= $f->text('name', 'Name') ?>
```

**Never repopulated:** `password()` and `file()` — for obvious security reasons.

**Select auto-select:** The option whose value matches `old('field')` gets `selected`.

**Checkbox auto-check:** If `old('agree')` is truthy, the checkbox gets `checked`.

**Radio auto-check:** If `old('color') === 'red'`, the radio with `value="red"` gets `checked`.

---

## Validation Errors

If `_validation_errors` flash data contains an error for a field, it's rendered as a `<span>` inside the wrapper.

```php
// If validation fails with "Email is required"
<?= form()->email('email', 'Email') ?>
```

Generates:

```html
<div>
    <label for="email">Email</label>
    <input type="email" name="email" id="email" value="">
    <span>Email is required</span>
</div>
```

If there's no error, the `<span>` is omitted entirely — no empty DOM nodes.

---

## Configuration

Pass a config array to `form()` to apply CSS classes (or any attributes) to every field:

```php
<?php $f = form([
    'wrapper' => ['class' => 'mb-4'],
    'label'   => ['class' => 'block text-sm font-medium'],
    'input'   => ['class' => 'border rounded w-full px-3 py-2'],
    'error'   => ['class' => 'text-red-500 text-sm mt-1'],
]) ?>

<?= $f->open('/users') ?>
<?= $f->text('name', 'Name') ?>
<?= $f->submit('Save') ?>
<?= $f->close() ?>
```

### Per-field override

Attributes passed to a field method override the config:

```php
<?= $f->text('name', 'Name', ['placeholder' => 'Your full name']) ?>
```

The input gets both the config class *and* the placeholder:

```html
<input type="text" name="name" id="name" class="border rounded w-full px-3 py-2" placeholder="Your full name">
```

---

## Custom Layouts (Escape Hatch)

When `$f->text()` produces too much DOM, build the field manually:

```php
<?php $f = form() ?>
<?= $f->open('/users') ?>

<div class="flex items-center gap-4">
    <?= $f->label('email', 'Email') ?>
    <?= $f->input('email', 'email', ['class' => 'flex-1']) ?>
    <?= $f->error('email') ?>
</div>

<?= $f->close() ?>
```

### Individual Pieces

| Method | Returns |
|---|---|
| `label($name, $text, $attrs = [])` | `<label>` tag |
| `input($name, $type = 'text', $attrs = [])` | `<input>` tag |
| `error($name)` | Error `<span>` or empty string |

These give you full control over the HTML structure while keeping the auto-wiring.

---

## Working with Arrays

Form fields can have array names. IDs are generated safely:

```php
<?php $f = form() ?>
<?= $f->text('user[name]', 'Name') ?>
```

Generates:

```html
<input type="text" name="user[name]" id="user-name" value="">
```

```php
<?php $f = form() ?>
<?= $f->text('user[profile][bio]', 'Bio') ?>
```

Generates:

```html
<input type="text" name="user[profile][bio]" id="user-profile-bio" value="">
```

---

## Security

### XSS Prevention

All text output is HTML-escaped via `_e()`:

- Label text
- Old input values
- Error messages
- Submit button text
- Select option labels

```php
<?php $f = form() ?>
<?= $f->text('name', '<script>alert(1)</script>') ?>
// Label renders as: &lt;script&gt;alert(1)&lt;/script&gt;
```

### CSRF Protection

```php
<?php $f = form() ?>
<?= $f->open('/users') ?>
// Automatically includes: <input type="hidden" name="_token" value="...">
```

Disable if needed (e.g., external API callback):

```php
<?php $f = form() ?>
<?= $f->open('/webhook', 'POST', [], false) ?>
```

### Sensitive Fields

`password()` and `file()` never repopulate old input — even if the browser sends it back.

---

## Select Dropdowns

```php
<?php $f = form() ?>
<?= $f->select('country', 'Country', [
    'us' => 'United States',
    'uk' => 'United Kingdom',
    'jp' => 'Japan',
]) ?>
```

Numeric keys work too:

```php
<?php $f = form() ?>
<?= $f->select('priority', 'Priority', [
    1 => 'Low',
    2 => 'Medium',
    3 => 'High',
]) ?>
```

---

## Checkboxes & Radios

### Checkbox

```php
<?php $f = form() ?>
<?= $f->checkbox('agree', 'I agree to the terms') ?>
```

Default value is `1`. Custom value:

```php
<?php $f = form() ?>
<?= $f->checkbox('newsletter', 'Subscribe', ['value' => 'yes']) ?>
```

### Radio

```php
<?php $f = form() ?>
<?= $f->radio('plan', 'Free', 'free') ?>
<?= $f->radio('plan', 'Pro', 'pro') ?>
<?= $f->radio('plan', 'Enterprise', 'enterprise') ?>
```

Each radio gets a unique ID based on name + value (`id="plan-free"`, `id="plan-pro"`, etc.).

---

## Hidden Fields

```php
<?php $f = form() ?>
<?= $f->hidden('id', $post->id) ?>
<?= $f->hidden('_action', 'draft') ?>
```

If `$value` is `null`, the `value` attribute is omitted.

---

## Submit Buttons

```php
<?php $f = form() ?>
<?= $f->submit('Save Changes') ?>
<?= $f->submit('Save', ['class' => 'btn-primary']) ?>
```

---

## Full Example: Edit Form

```php
<?php $f = form() ?>
<?= $f->open('/posts/' . $post->id, 'PUT') ?>

<?= $f->text('title', 'Title') ?>
<?= $f->textarea('body', 'Content') ?>
<?= $f->select('status', 'Status', [
    'draft' => 'Draft',
    'published' => 'Published',
]) ?>
<?= $f->checkbox('featured', 'Feature this post') ?>
<?= $f->file('thumbnail', 'Thumbnail') ?>
<?= $f->hidden('id', $post->id) ?>

<div class="flex gap-2">
    <?= $f->submit('Update', ['class' => 'btn-primary']) ?>
    <a href="/posts" class="btn-secondary">Cancel</a>
</div>

<?= $f->close() ?>
```

---

## How It Works

The `Form` class is a thin wrapper over three things Lightpack already provides:

1. **`form_open()`** — generates `<form>` with CSRF and method spoofing
2. **`old()`** — repopulates fields from flash session data
3. **`error()`** — displays validation errors from flash session data

The `Form` class just combines them into a single, clean API. No new concepts. No new conventions. Just less typing.
