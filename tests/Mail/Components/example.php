<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

use Lightpack\Mail\Components\Button;
use Lightpack\Mail\Components\Card;
use Lightpack\Mail\Components\Divider;
use Lightpack\Mail\Components\Grid;
use Lightpack\Mail\Components\Image;
use Lightpack\Mail\Components\Link;
use Lightpack\Mail\Components\ListComponent;
use Lightpack\Mail\Components\Quote;
use Lightpack\Mail\Components\Section;
use Lightpack\Mail\Components\Spacer;
use Lightpack\Mail\Components\Table;
use Lightpack\Mail\Components\Text;

// Create header section
$header = new Section();
$header->style('background-color', '#ffffff')
    ->padding(32);

$header->image('https://picsum.photos/200/50', 'Logo');
$header->spacer(20);
$header->bold('Welcome to Lightpack Framework')->size(32);
$header->text('Thank you for choosing our modern PHP framework');

// Create content section
$content = new Section();
$content->style('background-color', '#ffffff')
    ->style('width', '200px')
    ->style('text-align', 'center')
    ->padding(32);

$content->text('Hi John,');
$content->spacer(20);

$quote = new Quote();
$quote->content('Lightpack has transformed how we build web applications. Its simplicity and power are unmatched.')
    ->author('Sarah Johnson', 'Lead Developer at TechCorp')
    ->borderColor('#007bff');
$content->add($quote);

$content->spacer(30);

$grid = new Grid();
$grid->gap(16)
    ->column(function($col) {
        $col->add(
            (new Card())
                ->title('Fast & Lightweight')
                ->content('Built for speed and efficiency')
        );
    }, 33)
    ->column(function($col) {
        $col->add(
            (new Card())
                ->title('Modern Architecture')
                ->content('Using latest PHP features')
        );
    }, 33)
    ->column(function($col) {
        $col->add(
            (new Card())
                ->title('Developer Friendly')
                ->content('Great developer experience')
        );
    }, 34);
$content->add($grid);

$content->spacer(30);
$content->text('Key Features:');
$content->spacer(10);

$list = new ListComponent();
$list->bulletColor('#007bff')
    ->items([
        'Simple and intuitive API',
        'Powerful routing system',
        'Database abstraction layer',
        'Built-in security features',
        'Extensive documentation',
    ]);
$content->add($list);

$content->spacer(30);
$content->text('Framework Performance:');
$content->spacer(10);

$table = new Table();
$table->headers(['Metric', 'Value', 'Comparison'])
    ->rows([
        ['Response Time', '< 50ms', '2x faster'],
        ['Memory Usage', '< 2MB', '3x lighter'],
        ['Database Queries', '< 10 per request', 'Optimized'],
    ])
    ->striped()
    ->headerBgColor('#f8f9fa')
    ->stripedBgColor('#f8f9fa');
$content->add($table);

$content->spacer(30);
$content->text('Ready to get started? Check out our resources:');
$content->spacer(20);

$docs = new Button();
$docs->content('Read Documentation')
    ->href('https://lightpack.com/docs')
    ->color('#ffffff')
    ->backgroundColor('#007bff');
$content->add($docs);

$content->spacer(20);

$community = new Button();
$community->content('Join Community')
    ->href('https://lightpack.com/community')
    ->color('#ffffff')
    ->backgroundColor('#007bff');
$content->add($community);

// Create footer section
$footer = new Section();
$footer->style('background-color', '#ffffff')
    ->style('margin-right', '10px')
    ->padding(32);

$footer->divider();
$footer->text('Follow us on:');
$footer->spacer(10);
$footer->link('Twitter', 'https://twitter.com/lightpack');
$footer->link('GitHub', 'https://github.com/lightpack');
$footer->link('Discord', 'https://discord.gg/lightpack');
$footer->spacer(20);
$footer->text(' 2025 Lightpack Framework. All rights reserved.')
    ->color('#6c757d')
    ->align('center');
$footer->spacer(10);

// Generate email HTML
$email = new Section();
$email->add($header)
    ->add($content)
    ->add($footer);

// Save to file
file_put_contents(__DIR__ . '/example.html', $email->render());
