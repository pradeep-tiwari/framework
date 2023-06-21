<?php

namespace Lightpack\Console;

class Color
{
    const RED = "\033[0;31m";
    const GREEN = "\033[0;32m";
    const YELLOW = "\033[0;33m";
    const BLUE = "\033[0;34m";
    const RESET = "\033[0m";
    
    const BG_RED = "\033[41m";
    const BG_GREEN = "\033[42m";
    const BG_YELLOW = "\033[43m";
    const BG_BLUE = "\033[44m";
    const BG_RESET = "\033[49m";
    
    const RESET_LABEL = '<RESET>';   // Adjust the reset label as needed

    public function format($string)
    {
        $string = preg_replace_callback('/<(\w+)>/', function ($matches) {
            $label = strtoupper($matches[1]);
            if (defined('self::' . $label)) {
                return constant('self::' . $label);
            }
            return $matches[0];
        }, $string);

        $string = str_replace(self::RESET_LABEL, self::RESET . self::BG_RESET, $string);

        return $string;
    }

    public function info(string $text)
    {
        return Color::format("<BLUE>{$text}<RESET>");
    }

    public function success(string $text)
    {
        return Color::format("<GREEN>{$text}<RESET>");
    }

    public function error(string $text)
    {
        return Color::format("<RED>{$text}<RESET>");
    }

    public function warning(string $text)
    {
        return Color::format("<YELLOW>{$text}<RESET>");
    }

    public function infoLabel(?string $text = null)
    {
        $text = $text ?? ' INFO ';

        return Color::format("<BG_BLUE>{$text}<BG_RESET>");
    }

    public function successLabel(?string $text = null)
    {
        $text = $text ?? ' SUCCESS ';

        return Color::format("<BG_GREEN>{$text}<BG_RESET>");
    }

    public function errorLabel(?string $text = null)
    {
        $text = $text ?? ' ERROR ';

        return Color::format("<BG_RED>{$text}<BG_RESET>");
    }

    public function warningLabel(?string $text = null)
    {
        $text = $text ?? ' WARNING ';

        return Color::format("<BG_YELLOW>{$text}<BG_RESET>");
    }
}