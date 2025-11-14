<?php

namespace Lightpack\Console\Views;

class MailView
{
    public static function getTemplate()
    {
        return <<<'TEMPLATE'
<?php

namespace __NAMESPACE__;

use Lightpack\Mail\Mail;

class __MAIL_NAME__ extends Mail
{
    public function dispatch(array $payload = [])
    {
        // ...
    }
}
TEMPLATE;
    }
}
