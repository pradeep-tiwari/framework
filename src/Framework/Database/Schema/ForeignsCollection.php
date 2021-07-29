<?php

namespace Lightpack\Database\Schema;

class ForeignsCollection
{
    /**
     * @var array Lightpack\Database\Schema\Foreign
     */
    private $foreigns = [];

    public function add(Foreign $foreign)
    {
        $this->foreigns[] = $foreign;
    }

    public function compile()
    {
        $constraints = [];

        foreach ($this->foreigns as $foreign) {
            $constraints[] = $foreign->compile();
        }

        return implode(', ', $constraints);
    }
}
