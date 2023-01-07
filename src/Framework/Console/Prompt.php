<?php

namespace Lightpack\Console;

class Prompt
{
    public static function ask($question, $default = null)
    {
        echo $question . ' ';

        if ($default) {
            echo "[$default] ";
        }

        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        $response = trim($line);

        if (empty($response)) {
            $response = $default;
        }

        return $response;
    }

    public static function askBoolean($question, $default = null)
    {
        $response = self::ask($question, $default);

        if (strtolower($response) === 'y') {
            return true;
        }

        return false;
    }
}