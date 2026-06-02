<?php

namespace Tests\View;

use Lightpack\Container\Container;
use Lightpack\Session\DriverInterface;
use Lightpack\Session\Session;
use Lightpack\View\Form;
use PHPUnit\Framework\TestCase;

class FormTest extends TestCase
{
    private Session $session;

    protected function setUp(): void
    {
        $driver = $this->createMock(DriverInterface::class);

        // Seed consistent flash data for old() and error() helpers
        $driver->method('get')
            ->willReturnMap([
                ['_token', null, 'test-csrf-token-123'],
                ['_old_input', null, [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                    'role' => 'admin',
                    'agree' => '1',
                    'color' => 'red',
                    'bio' => '<script>alert(1)</script>',
                    'tags' => ['php', 'js'],
                ]],
                ['_validation_errors', null, [
                    'name' => 'Name is required',
                    'email' => 'Invalid email address',
                    'bio' => 'Bio is too short',
                    'select_field' => 'Role is required',
                    'avatar' => 'File too large',
                    'xss_error' => '<script>evil</script>',
                ]],
            ]);

        $config = $this->createMock(\Lightpack\Config\Config::class);
        $config->method('get')
            ->willReturnMap([
                ['session.name', 'lightpack_session', 'lightpack_session'],
                ['session.lifetime', 7200, 7200],
                ['session.same_site', 'lax', 'lax'],
            ]);

        $this->session = new Session($driver, $config);

        // Register session in container so session() helper works
        $container = Container::getInstance();
        $container->register('session', function () {
            return $this->session;
        });
    }

    protected function tearDown(): void
    {
        Container::destroy();
    }

    /** @test */
    public function formUtilityReturnsFormInstance()
    {
        $form = form();

        $this->assertInstanceOf(Form::class, $form);
    }

    /** @test */
    public function formUtilityReturnsNewInstanceEachTime()
    {
        $form1 = form();
        $form2 = form();

        $this->assertNotSame($form1, $form2);
    }

    /** @test */
    public function formUtilityAcceptsConfig()
    {
        $form = form([
            'wrapper' => ['class' => 'mb-4'],
        ]);

        $html = $form->text('name', 'Name');

        $this->assertStringContainsString('<div class="mb-4">', $html);
    }

    /** @test */
    public function openReturnsFormTagWithCsrf()
    {
        $form = new Form;
        $html = $form->open('/users', 'POST');

        $this->assertStringStartsWith('<form', $html);
        $this->assertStringContainsString('action="/users"', $html);
        $this->assertStringContainsString('method="POST"', $html);
        $this->assertStringContainsString('name="_token"', $html);
        $this->assertStringContainsString('value="test-csrf-token-123"', $html);
    }

    /** @test */
    public function openReturnsFormTagWithoutCsrf()
    {
        $form = new Form;
        $html = $form->open('/users', 'POST', [], false);

        $this->assertStringNotContainsString('name="_token"', $html);
    }

    /** @test */
    public function openSpoofsPutMethod()
    {
        $form = new Form;
        $html = $form->open('/users/1', 'PUT');

        $this->assertStringContainsString('method="POST"', $html);
        $this->assertStringContainsString('name="_method"', $html);
        $this->assertStringContainsString('value="PUT"', $html);
    }

    /** @test */
    public function closeReturnsClosingFormTag()
    {
        $form = new Form;

        $this->assertEquals('</form>', $form->close());
    }

    /** @test */
    public function textFieldGeneratesLabelInputAndWrapper()
    {
        $form = new Form;
        $html = $form->text('name', 'Full Name');

        $this->assertStringContainsString('<div>', $html);
        $this->assertStringContainsString('<label for="name">Full Name</label>', $html);
        $this->assertStringContainsString('<input type="text" name="name" id="name"', $html);
        $this->assertStringContainsString('</div>', $html);
    }

    /** @test */
    public function textFieldRepopulatesFromOldInput()
    {
        $form = new Form;
        $html = $form->text('name', 'Name');

        $this->assertStringContainsString('value="John Doe"', $html);
    }

    /** @test */
    public function textFieldShowsErrorWhenPresent()
    {
        $form = new Form;
        $html = $form->text('name', 'Name');

        $this->assertStringContainsString('>Name is required<', $html);
        $this->assertStringContainsString('<span', $html);
    }

    /** @test */
    public function textFieldDoesNotShowErrorWhenNone()
    {
        $form = new Form;
        $html = $form->text('role', 'Role');

        $this->assertStringNotContainsString('<span', $html);
    }

    /** @test */
    public function emailFieldGeneratesEmailInput()
    {
        $form = new Form;
        $html = $form->email('email', 'Email');

        $this->assertStringContainsString('<input type="email"', $html);
        $this->assertStringContainsString('value="john@example.com"', $html);
    }

    /** @test */
    public function passwordFieldNeverRepopulates()
    {
        $form = new Form;
        $html = $form->password('password', 'Password');

        $this->assertStringContainsString('type="password"', $html);
        $this->assertStringNotContainsString('value="', $html);
    }

    /** @test */
    public function textareaFieldGeneratesTextareaWithOldValue()
    {
        $form = new Form;
        $html = $form->textarea('bio', 'Bio');

        $this->assertStringContainsString('<textarea', $html);
        $this->assertStringContainsString('>&lt;script&gt;alert(1)&lt;/script&gt;</textarea>', $html);
    }

    /** @test */
    public function selectFieldGeneratesOptionsAndSelectsOldValue()
    {
        $form = new Form;
        $html = $form->select('role', 'Role', ['admin' => 'Admin', 'user' => 'User']);

        $this->assertStringContainsString('<select', $html);
        $this->assertStringContainsString('<option value="admin" selected>Admin</option>', $html);
        $this->assertStringContainsString('<option value="user">User</option>', $html);
    }

    /** @test */
    public function selectFieldWithNoOldValueDoesNotSelectAnything()
    {
        $form = new Form;
        $html = $form->select('status', 'Status', ['active' => 'Active', 'inactive' => 'Inactive']);

        $this->assertStringNotContainsString(' selected', $html);
    }

    /** @test */
    public function checkboxIsCheckedWhenOldValueIsTruthy()
    {
        $form = new Form;
        $html = $form->checkbox('agree', 'I agree');

        $this->assertStringContainsString('type="checkbox"', $html);
        $this->assertStringContainsString('checked="checked"', $html);
    }

    /** @test */
    public function checkboxIsUncheckedWhenNoOldValue()
    {
        $form = new Form;
        $html = $form->checkbox('newsletter', 'Subscribe');

        $this->assertStringNotContainsString('checked', $html);
    }

    /** @test */
    public function radioIsCheckedWhenOldValueMatches()
    {
        $form = new Form;
        $html = $form->radio('color', 'Red', 'red');

        $this->assertStringContainsString('type="radio"', $html);
        $this->assertStringContainsString('checked="checked"', $html);
    }

    /** @test */
    public function radioIsUncheckedWhenOldValueDoesNotMatch()
    {
        $form = new Form;
        $html = $form->radio('color', 'Blue', 'blue');

        $this->assertStringNotContainsString('checked', $html);
    }

    /** @test */
    public function fileFieldNeverRepopulates()
    {
        $form = new Form;
        $html = $form->file('avatar', 'Avatar');

        $this->assertStringContainsString('type="file"', $html);
        $this->assertStringNotContainsString('value="', $html);
    }

    /** @test */
    public function hiddenFieldUsesExplicitValue()
    {
        $form = new Form;
        $html = $form->hidden('id', '42');

        $this->assertStringContainsString('type="hidden"', $html);
        $this->assertStringContainsString('value="42"', $html);
    }

    /** @test */
    public function hiddenFieldWithNullValueOmitsValue()
    {
        $form = new Form;
        $html = $form->hidden('secret');

        $this->assertStringContainsString('type="hidden"', $html);
        $this->assertStringNotContainsString('value="', $html);
    }

    /** @test */
    public function submitGeneratesButton()
    {
        $form = new Form;
        $html = $form->submit('Save Changes');

        $this->assertStringContainsString('<button type="submit">Save Changes</button>', $html);
    }

    /** @test */
    public function submitAcceptsAttributes()
    {
        $form = new Form;
        $html = $form->submit('Save', ['class' => 'btn-primary']);

        $this->assertStringContainsString('type="submit"', $html);
        $this->assertStringContainsString('class="btn-primary"', $html);
        $this->assertStringContainsString('>Save</button>', $html);
    }

    /** @test */
    public function labelGeneratesLabelTag()
    {
        $form = new Form;
        $html = $form->label('name', 'Full Name');

        $this->assertStringContainsString('<label for="name">Full Name</label>', $html);
    }

    /** @test */
    public function inputGeneratesBareInput()
    {
        $form = new Form;
        $html = $form->input('name', 'text');

        $this->assertStringContainsString('<input type="text" name="name" id="name"', $html);
    }

    /** @test */
    public function inputRepopulatesFromOldInput()
    {
        $form = new Form;
        $html = $form->input('name', 'text');

        $this->assertStringContainsString('value="John Doe"', $html);
    }

    /** @test */
    public function errorReturnsEmptyStringWhenNoError()
    {
        $form = new Form;
        $html = $form->error('role');

        $this->assertEquals('', $html);
    }

    /** @test */
    public function errorReturnsSpanWhenErrorExists()
    {
        $form = new Form;
        $html = $form->error('name');

        $this->assertStringContainsString('<span', $html);
        $this->assertStringContainsString('>Name is required<', $html);
    }

    /** @test */
    public function configClassesAreAppliedToWrapper()
    {
        $form = new Form([
            'wrapper' => ['class' => 'field mb-4'],
        ]);
        $html = $form->text('name', 'Name');

        $this->assertStringContainsString('<div class="field mb-4">', $html);
    }

    /** @test */
    public function configClassesAreAppliedToLabel()
    {
        $form = new Form([
            'label' => ['class' => 'label'],
        ]);
        $html = $form->text('name', 'Name');

        $this->assertStringContainsString('for="name"', $html);
        $this->assertStringContainsString('class="label"', $html);
    }

    /** @test */
    public function configClassesAreAppliedToInput()
    {
        $form = new Form([
            'input' => ['class' => 'input'],
        ]);
        $html = $form->text('name', 'Name');

        $this->assertStringContainsString('class="input"', $html);
    }

    /** @test */
    public function configClassesAreAppliedToError()
    {
        $form = new Form([
            'error' => ['class' => 'help is-danger'],
        ]);
        $html = $form->text('name', 'Name');

        $this->assertStringContainsString('<span class="help is-danger">', $html);
    }

    /** @test */
    public function customAttributesCanOverrideConfig()
    {
        $form = new Form([
            'input' => ['class' => 'input'],
        ]);
        $html = $form->text('name', 'Name', ['class' => 'form-control', 'placeholder' => 'Your name']);

        $this->assertStringContainsString('class="form-control"', $html);
        $this->assertStringContainsString('placeholder="Your name"', $html);
    }

    /** @test */
    public function customAttributesOnWrapper()
    {
        $form = new Form;
        $html = $form->text('name', 'Name', ['data-validate' => 'true']);

        // Custom attrs should be on input, not wrapper
        $this->assertStringContainsString('data-validate="true"', $html);
    }

    /** @test */
    public function arrayNameGeneratesValidId()
    {
        $form = new Form;
        $html = $form->text('user[name]', 'Name');

        $this->assertStringContainsString('name="user[name]"', $html);
        $this->assertStringContainsString('id="user-name"', $html);
    }

    /** @test */
    public function specialCharactersInNameAreReplacedForId()
    {
        $form = new Form;
        $html = $form->text('user[profile][bio]', 'Bio');

        $this->assertStringContainsString('id="user-profile-bio"', $html);
    }

    /** @test */
    public function textareaOldInputIsHtmlEscaped()
    {
        $form = new Form;
        $html = $form->textarea('bio', 'Bio');

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
    }

    /** @test */
    public function labelTextIsHtmlEscaped()
    {
        $form = new Form;
        $html = $form->label('name', '<b>Name</b>');

        $this->assertStringNotContainsString('<b>', $html);
        $this->assertStringContainsString('&lt;b&gt;Name&lt;/b&gt;', $html);
    }

    /** @test */
    public function submitTextIsHtmlEscaped()
    {
        $form = new Form;
        $html = $form->submit('Save <script>');

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    /** @test */
    public function errorMessageIsHtmlEscaped()
    {
        $form = new Form;
        $html = $form->error('xss_error');

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;evil&lt;/script&gt;', $html);
    }

    /** @test */
    public function passwordFieldShowsErrorWhenPresent()
    {
        $form = new Form;
        $html = $form->password('password', 'Password');

        // password field should not have error since 'password' key not in errors
        $this->assertStringNotContainsString('<span', $html);
    }

    /** @test */
    public function fileFieldShowsErrorWhenPresent()
    {
        $form = new Form;
        $html = $form->file('avatar', 'Avatar');

        $this->assertStringContainsString('>File too large<', $html);
    }

    /** @test */
    public function radioGeneratesUniqueIdPerValue()
    {
        $form = new Form;
        $red = $form->radio('color', 'Red', 'red');
        $blue = $form->radio('color', 'Blue', 'blue');

        $this->assertStringContainsString('id="color-red"', $red);
        $this->assertStringContainsString('id="color-blue"', $blue);
    }

    /** @test */
    public function checkboxUsesDefaultValueOfOne()
    {
        $form = new Form;
        $html = $form->checkbox('agree', 'I agree');

        $this->assertStringContainsString('value="1"', $html);
    }

    /** @test */
    public function checkboxAcceptsCustomValue()
    {
        $form = new Form;
        $html = $form->checkbox('terms', 'Accept', ['value' => 'yes']);

        $this->assertStringContainsString('value="yes"', $html);
    }

    /** @test */
    public function selectOptionsCanHaveNumericKeys()
    {
        $form = new Form;
        $html = $form->select('priority', 'Priority', [1 => 'Low', 2 => 'High']);

        $this->assertStringContainsString('<option value="1">Low</option>', $html);
        $this->assertStringContainsString('<option value="2">High</option>', $html);
    }

    /** @test */
    public function hiddenFieldEscapesNameAndValue()
    {
        $form = new Form;
        $html = $form->hidden('field" onclick', 'value" onclick');

        $this->assertStringContainsString('name="field&quot; onclick"', $html);
        $this->assertStringContainsString('value="value&quot; onclick"', $html);
    }

    /** @test */
    public function inputMethodDoesNotRepopulatePasswordOrFile()
    {
        $form = new Form;
        $password = $form->input('password', 'password');
        $file = $form->input('avatar', 'file');

        $this->assertStringNotContainsString('value="', $password);
        $this->assertStringNotContainsString('value="', $file);
    }

    /** @test */
    public function inputMethodRepopulatesText()
    {
        $form = new Form;
        $html = $form->input('name', 'text');

        $this->assertStringContainsString('value="John Doe"', $html);
    }

    /** @test */
    public function textFieldCustomTypeAttributeIsOverridden()
    {
        $form = new Form;
        $html = $form->text('phone', 'Phone', ['type' => 'tel']);

        // text() method should force type="text", not accept override
        $this->assertStringContainsString('type="text"', $html);
    }

    /** @test */
    public function wrapperOnlyContainsLabelInputAndError()
    {
        $form = new Form;
        $html = $form->text('name', 'Name');

        // Should have exactly: label, input, error span
        $this->assertEquals(1, substr_count($html, '<label'));
        $this->assertEquals(1, substr_count($html, '<input'));
        $this->assertEquals(1, substr_count($html, '<span'));
    }

    /** @test */
    public function fieldWithoutErrorDoesNotRenderEmptySpan()
    {
        $form = new Form;
        $html = $form->text('role', 'Role');

        $this->assertStringNotContainsString('<span', $html);
    }

    /** @test */
    public function booleanAttributeRendering()
    {
        $form = new Form;
        $html = $form->input('name', 'text', ['required' => true, 'disabled' => false, 'readonly' => null]);

        $this->assertStringContainsString(' required', $html);
        $this->assertStringNotContainsString('disabled', $html);
        $this->assertStringNotContainsString('readonly', $html);
    }

    /** @test */
    public function emailFieldShowsError()
    {
        $form = new Form;
        $html = $form->email('email', 'Email');

        $this->assertStringContainsString('>Invalid email address<', $html);
    }

    /** @test */
    public function textareaFieldShowsError()
    {
        $form = new Form;
        $html = $form->textarea('bio', 'Bio');

        $this->assertStringContainsString('>Bio is too short<', $html);
    }

    /** @test */
    public function selectFieldShowsError()
    {
        $form = new Form;
        $html = $form->select('select_field', 'Role', ['admin' => 'Admin']);

        $this->assertStringContainsString('>Role is required<', $html);
    }
}
