<?php

namespace Lightpack\Mail\Components;

class Grid extends Component
{
    protected array $columns = [];
    protected int $gap = 20;
    protected array $columnWidths = [];

    /**
     * Add a column to the grid
     */
    public function column(callable $callback, int $width = null): self
    {
        $column = new Section;
        $callback($column);
        $this->columns[] = $column;
        
        if ($width !== null) {
            $this->columnWidths[] = $width;
        } else {
            // If no width specified, distribute evenly
            $count = count($this->columns);
            $this->columnWidths = array_fill(0, $count, floor(100 / $count));
        }

        return $this;
    }

    /**
     * Set gap between columns
     */
    public function gap(int $pixels): self
    {
        $this->gap = $pixels;
        return $this;
    }

    /**
     * Render the grid component
     */
    public function render(): string
    {
        if (empty($this->columns)) {
            return '';
        }

        $styles = array_merge([
            'width' => '100%',
            'margin' => '0 0 16px 0',
            'border-spacing' => "{$this->gap}px 0",
            'border-collapse' => 'separate',
        ], $this->styles);

        $styleAttr = $this->buildStyleAttribute($styles);

        $html = "<table {$styleAttr} cellpadding=\"0\" cellspacing=\"0\" border=\"0\">" . PHP_EOL;
        $html .= "<tr valign=\"top\">" . PHP_EOL;

        foreach ($this->columns as $index => $column) {
            $width = $this->columnWidths[$index];
            $html .= "<td width=\"{$width}%\" style=\"vertical-align: top;\">" . PHP_EOL;
            $html .= $column->render() . PHP_EOL;
            $html .= "</td>" . PHP_EOL;
        }

        $html .= "</tr>" . PHP_EOL;
        $html .= "</table>" . PHP_EOL;

        // Add mobile responsiveness
        $html .= "<!--[if !mso]><!-->" . PHP_EOL;
        $html .= "<style>" . PHP_EOL;
        $html .= "@media only screen and (max-width: 600px) {" . PHP_EOL;
        $html .= "  table td { display: block !important; width: 100% !important; margin-bottom: 20px !important; }" . PHP_EOL;
        $html .= "}" . PHP_EOL;
        $html .= "</style>" . PHP_EOL;
        $html .= "<!--<![endif]-->";

        return $html;
    }
}
