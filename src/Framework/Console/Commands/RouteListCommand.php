<?php

namespace Lightpack\Console\Commands;

use Lightpack\App;
use Lightpack\Console\Command;
use Lightpack\Routing\Route;

class RouteListCommand extends Command
{
    protected string $description = 'List all registered application routes';

    public function run()
    {
        if (! defined('DIR_BOOT')) {
            $this->output->error('No application routes found (DIR_BOOT is not defined).');

            return self::FAILURE;
        }

        // Console boot does not load routes; populate the registry.
        App::bootRoutes();

        $rows = [];

        foreach (route()->routes() as $route) {
            $rows[] = [
                $route->getVerb(),
                $route->getUri(),
                $this->action($route),
                $route->getName(),
                implode(',', $route->getFilters()),
            ];
        }

        if ($rows === []) {
            $this->output->line('No routes registered.');

            return self::SUCCESS;
        }

        $this->table($rows);

        return self::SUCCESS;
    }

    private function action(Route $route): string
    {
        $controller = $route->getController();
        $short = substr(strrchr($controller, '\\'), 1) ?: $controller;

        return $short . '@' . $route->getAction();
    }

    private function table(array $rows): void
    {
        $columns = ['METHOD', 'URI', 'ACTION', 'NAME', 'FILTERS'];
        $widths = array_map('strlen', $columns);

        foreach ($rows as $row) {
            foreach ($row as $i => $cell) {
                $widths[$i] = max($widths[$i], strlen((string) $cell));
            }
        }

        $this->output->newline();
        $this->row($columns, $widths);

        foreach ($rows as $row) {
            $this->row($row, $widths);
        }

        $this->output->newline();
    }

    private function row(array $row, array $widths): void
    {
        $line = '';

        foreach ($row as $i => $cell) {
            $line .= str_pad('  ' . $cell, $widths[$i] + 2);
        }

        $this->output->line(rtrim($line));
    }
}
