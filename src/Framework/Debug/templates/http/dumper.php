<!DOCTYPE html>
<html lang="en">

<head>
    <title>Debug Dump</title>
    <meta charset="utf-8">
    <meta name="robots" content="noindex,nofollow" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <style>
        <?php require __DIR__ . '/../css/styles.css' ?>
        <?php require __DIR__ . '/../css/dumper.css' ?>
    </style>
</head>

<body>
    <?php
    function dumpNode($value): string
    {
        if (is_array($value)) {
            if (empty($value)) {
                return '<span class="dump-type">array(0)</span> {}';
            }

            $count = count($value);
            $html = '<details class="dump-node" open>';
            $html .= '<summary class="dump-node-summary"><span class="dump-type">array(' . $count . ')</span></summary>';
            $html .= '<div class="dump-node-content">';

            foreach ($value as $key => $item) {
                $html .= '<div class="dump-row">';
                $html .= '<span class="dump-key">' . htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8') . '</span>';
                $html .= ' <span class="dump-arrow">=></span> ';
                $html .= dumpNode($item);
                $html .= '</div>';
            }

            $html .= '</div></details>';

            return $html;
        }

        if (is_string($value)) {
            return '<span class="dump-string">"' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"</span>';
        }

        if (is_int($value) || is_float($value)) {
            return '<span class="dump-number">' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '</span>';
        }

        if (is_bool($value)) {
            return '<span class="dump-bool">' . ($value ? 'true' : 'false') . '</span>';
        }

        if (is_null($value)) {
            return '<span class="dump-null">null</span>';
        }

        return '<span class="dump-other">' . htmlspecialchars(print_r($value, true), ENT_QUOTES, 'UTF-8') . '</span>';
    }
        ?>

    <div class="container">
        <div class="dump-header">
            <strong>Debug Dump</strong>
            <span class="dump-meta"><?= count($args) ?> variable<?= count($args) === 1 ? '' : 's' ?></span>
        </div>

        <?php foreach ($args as $index => $arg) : ?>
            <?php
                $label = null;
            if (is_string($index)) {
                $label = $index;
            } elseif (is_array($arg) && count($arg) === 1) {
                $key = array_key_first($arg);
                if (is_string($key)) {
                    $label = $key;
                    $arg = reset($arg);
                }
            }
            $label ??= 'Variable #' . ($index + 1);
            ?>
            <details class="dump-details" open>
                <summary class="dump-summary"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></summary>
                <div class="code-preview">
                    <?= dumpNode($arg) ?>
                </div>
            </details>
        <?php endforeach ?>
    </div>
</body>

</html>