<?php

namespace Lightpack\Debug;

class Dumper
{
    public function varDump(...$args)
    {
        $this->dump('var_dump', $args);
    }

    /**
     * @deprecated Use varDump() instead.
     * This method will be removed in a future release.
     */
    public function printDump(...$args)
    {
        $this->dump('print_r', $args);
    }

    private function dump($dumpFunction, $args)
    {
        if (! get_env('APP_DEBUG', true)) {
            return;
        }

        if ('cli' === PHP_SAPI) {
            $this->dumpCli($dumpFunction, $args);
        } else {
            $this->dumpHtml($dumpFunction, $args);
        }
    }

    private function dumpCli($dumpFunction, $args)
    {
        fwrite(STDERR, "\033[31m");

        foreach ($args as $arg) {
            $dumpFunction($arg);
            fwrite(STDERR, PHP_EOL . '---' . PHP_EOL);
        }

        fwrite(STDERR, "\033[0m");
    }

    private function dumpHtml($dumpFunction, $args)
    {
        $template = __DIR__ . '/templates/http/dumper.php';

        $this->render($template, [
            'args' => $args,
            'dump_function' => $dumpFunction,
        ]);
    }

    private function render($template, $data)
    {
        // Clean the output buffer first.
        while (\ob_get_level() !== 0) {
            \ob_end_clean();
        }

        extract($data);
        ob_start();
        require $template;
        echo ob_get_clean();
        \flush();
    }
}
