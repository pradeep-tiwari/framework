<?php

namespace Lightpack\Database\Schema;

class KeysCollection
{
    /**
     * @var array Lightpack\Database\Schema\Key
     */
    private $keys = [];

    public function add(Key $key)
    {
        $this->keys[] = $key;
    }

    public function compile()
    {
        $constraints = [];

        foreach ($this->keys as $key) {
            $constraints[] = $key->compile();
        }

        return implode(', ', $constraints);
    }
}
