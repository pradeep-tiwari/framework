<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

use Lightpack\Mail\Components\Button;
use Lightpack\Mail\Components\Image;
use Lightpack\Mail\Components\Section;
use Lightpack\Mail\Components\Spacer;
use Lightpack\Mail\Components\Table;
use Lightpack\Mail\Components\Text;

// Create header section
$header = new Section();
$header->style('background-color', '#ffffff')
    ->style('border-bottom', '1px solid #e0e0e0')
    ->padding(24);

$header->image('https://picsum.photos/120/30', 'Logo')
    ->style('margin', '0');
$header->spacer(16);
$header->text('INVOICE #INV-2025-001')
    ->style('font-size', '14px')
    ->style('font-weight', 'bold')
    ->style('color', '#1a1a1a');
$header->text('February 9, 2025')
    ->style('font-size', '14px')
    ->style('color', '#666666');

// Create content section
$content = new Section();
$content->style('background-color', '#ffffff')
    ->padding(24);

// Invoice details table
$table = new Table();
$table->headers(['Item', 'Qty', 'Price', 'Total'])
    ->rows([
        ['MacBook Pro M3', '1', '$1,999', '$1,999'],
        ['Magic Keyboard', '1', '$299', '$299'],
        ['Magic Mouse', '1', '$99', '$99'],
        ['USB-C Cable', '2', '$19', '$38'],
        ['Display Adapter', '1', '$69', '$69'],
    ])
    ->style('width', '100%')
    ->style('margin-bottom', '24px')
    ->style('font-size', '14px')
    ->style('font-weight', 'normal')
    ->style('border-collapse', 'collapse')
    ->headerBgColor('#f8f9fa')
    ->borderColor('#e0e0e0')
    ->bordered(true);

$content->add($table);

$content->spacer(24);

// Subtotal and total
$content->text('Subtotal: $2,504')
    ->style('text-align', 'right')
    ->style('font-size', '14px')
    ->style('color', '#666666');
$content->text('Tax (8.25%): $206.58')
    ->style('text-align', 'right')
    ->style('font-size', '14px')
    ->style('color', '#666666');
$content->text('Total: $2,710.58')
    ->style('text-align', 'right')
    ->style('font-size', '14px')
    ->style('font-weight', 'bold')
    ->style('color', '#1a1a1a');

$content->spacer(24);

// Payment button
$pay = new Button();
$pay->content('Pay Now')
    ->href('https://lightpack.com/pay/inv-2025-001')
    ->color('#ffffff')
    ->backgroundColor('#22c55e')
    ->style('padding', '12px 32px')
    ->style('font-size', '14px');
$content->add($pay);

// Create footer section
$footer = new Section();
$footer->style('background-color', '#f8f9fa')
    ->style('border-top', '1px solid #e0e0e0')
    ->padding(24);

$footer->text('Thank you for your business!')
    ->style('font-size', '14px')
    ->style('color', '#666666')
    ->align('center');

$footer->text('Lightpack Inc. • 123 Tech Street, San Francisco, CA 94105')
    ->style('font-size', '14px')
    ->style('color', '#666666')
    ->align('center');

// Generate email HTML
$email = new Section();
$email->asRoot()
    ->add($header)
    ->add($content)
    ->add($footer);

// Save to file
file_put_contents(__DIR__ . '/example.html', $email->render());
