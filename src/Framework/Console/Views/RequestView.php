<?php

namespace Lightpack\Console\Views;

class RequestView
{
    public static function getTemplate()
    {
        return <<<'TEMPLATE'
<?php

namespace __NAMESPACE__;

use Lightpack\Http\FormRequest;

class __REQUEST_NAME__ extends FormRequest
{
    public function rules()
    {
        // $this->validator->field('')->required();
    }
}
TEMPLATE;
    }
}
