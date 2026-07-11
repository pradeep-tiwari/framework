<?php

namespace Lightpack\Exceptions;

class RecordNotFoundException extends HttpException
{
    public function __construct()
    {
        parent::__construct('Record not found.', 404);
    }
}
