<?php

namespace Lightpack\Database\Schema;

class KeysCollection
{
    /**
     * @var array Lightpack\Database\Schema\Foreign
     */
    private $keys = [];

    public function add(Foreign $foreign)
    {
        $this->keys[] = $foreign;
    }

    public function compile()
    {
        $constraints = [];

        foreach ($this->keys as $foreign) {
            $constraints[] = $foreign->compile();
        }

        return implode(', ', $constraints);
    }
}
