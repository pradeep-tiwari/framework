<?php

namespace Lightpack\Mail\Components;

class Table extends Component
{
    protected array $headers = [];
    protected array $rows = [];
    protected array $columnStyles = [];
    protected bool $striped = false;
    protected bool $bordered = true;
    protected string $headerBgColor = '#f8f9fa';
    protected string $stripedBgColor = '#f8f9fa';
    protected string $borderColor = '#e0e0e0';

    /**
     * Set table headers
     */
    public function headers(array $headers): self
    {
        $this->headers = $headers;
        return $this;
    }

    /**
     * Add a row to the table
     */
    public function row(array $cells): self
    {
        $this->rows[] = $cells;
        return $this;
    }

    /**
     * Add multiple rows at once
     */
    public function rows(array $rows): self
    {
        foreach ($rows as $row) {
            $this->row($row);
        }
        return $this;
    }

    /**
     * Set column styles
     */
    public function columnStyle(int $index, array $styles): self
    {
        $this->columnStyles[$index] = $styles;
        return $this;
    }

    /**
     * Enable/disable striped rows
     */
    public function striped(bool $striped = true): self
    {
        $this->striped = $striped;
        return $this;
    }

    /**
     * Enable/disable borders
     */
    public function bordered(bool $bordered = true): self
    {
        $this->bordered = $bordered;
        return $this;
    }

    /**
     * Set header background color
     */
    public function headerBgColor(string $color): self
    {
        $this->headerBgColor = $color;
        return $this;
    }

    /**
     * Set striped row background color
     */
    public function stripedBgColor(string $color): self
    {
        $this->stripedBgColor = $color;
        return $this;
    }

    /**
     * Set border color
     */
    public function borderColor(string $color): self
    {
        $this->borderColor = $color;
        return $this;
    }

    /**
     * Render the table component
     */
    public function render(): string
    {
        $styles = array_merge([
            'width' => '100%',
            'margin' => '0 0 16px 0',
            'border-collapse' => 'collapse',
        ], $this->styles);

        if ($this->bordered) {
            $styles['border'] = "1px solid {$this->borderColor}";
        }

        $styleAttr = $this->buildStyleAttribute($styles);
        $html = "<table {$styleAttr}>" . PHP_EOL;

        // Render headers
        if (!empty($this->headers)) {
            $html .= "<thead>" . PHP_EOL . "<tr>" . PHP_EOL;
            foreach ($this->headers as $index => $header) {
                $cellStyles = [
                    'padding' => '12px',
                    'text-align' => 'left',
                    'background-color' => $this->headerBgColor,
                    'font-weight' => 'bold',
                ];
                if ($this->bordered) {
                    $cellStyles['border'] = "1px solid {$this->borderColor}";
                }
                if (isset($this->columnStyles[$index])) {
                    $cellStyles = array_merge($cellStyles, $this->columnStyles[$index]);
                }
                $cellStyleAttr = $this->buildStyleAttribute($cellStyles);
                $html .= "<th {$cellStyleAttr}>{$header}</th>" . PHP_EOL;
            }
            $html .= "</tr>" . PHP_EOL . "</thead>" . PHP_EOL;
        }

        // Render rows
        if (!empty($this->rows)) {
            $html .= "<tbody>" . PHP_EOL;
            foreach ($this->rows as $rowIndex => $row) {
                $html .= "<tr>" . PHP_EOL;
                foreach ($row as $colIndex => $cell) {
                    $cellStyles = [
                        'padding' => '12px',
                        'text-align' => 'left',
                    ];
                    if ($this->bordered) {
                        $cellStyles['border'] = "1px solid {$this->borderColor}";
                    }
                    if ($this->striped && $rowIndex % 2 === 1) {
                        $cellStyles['background-color'] = $this->stripedBgColor;
                    }
                    if (isset($this->columnStyles[$colIndex])) {
                        $cellStyles = array_merge($cellStyles, $this->columnStyles[$colIndex]);
                    }
                    $cellStyleAttr = $this->buildStyleAttribute($cellStyles);
                    $html .= "<td {$cellStyleAttr}>{$cell}</td>" . PHP_EOL;
                }
                $html .= "</tr>" . PHP_EOL;
            }
            $html .= "</tbody>" . PHP_EOL;
        }

        $html .= "</table>";

        return $html;
    }
}
