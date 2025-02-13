<?php

namespace Lightpack\Faker;

/**
 * Faker class for generating fake data
 *
 * @method string firstName()     Returns a random first name
 * @method string lastName()      Returns a random last name
 * @method string email()         Returns a random email address
 * @method string username()      Returns a random username
 * @method string password()      Returns a random password
 * @method string phone()         Returns a random phone number
 * @method string url()          Returns a random URL
 * @method string ipv4()         Returns a random IPv4 address
 * @method string slug()         Returns a URL-friendly slug
 * @method string address()      Returns a full address
 * @method string uuid()         Returns a UUID v4
 * @method string hash(int $length = 32) Returns a random hash
 * @method string token()        Returns a random token
 * @method string pastDate(string $format = 'Y-m-d') Returns a past date
 * @method string futureDate(string $format = 'Y-m-d') Returns a future date
 * @method string time()         Returns a random time
 * 
 * Content Generation Methods:
 * @method string article(string $format = 'text')  Returns a full article in text or HTML format
 * @method string text(string $format = 'text')    Returns a paragraph in text or HTML format
 * @method string title(string $format = 'text')    Returns a title in text or HTML format
 * @method string intro(string $format = 'text')    Returns an intro in text or HTML format
 * @method string conclusion(string $format = 'text') Returns a conclusion in text or HTML format
 * @method string markdown()     Returns content in markdown format
 * @method string html()         Returns content in HTML format
 */
class Faker 
{
    private array $data;
    private array $used = [];
    private bool $unique = false;
    
    public function __construct() 
    {
        $path = __DIR__ . '/faker.json';
        $this->data = json_decode(file_get_contents($path), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Failed to load faker data');
        }
    }
    
    public function __call(string $name, array $args): mixed 
    {
        if (!isset($this->data[$name])) {
            throw new \RuntimeException("Unknown faker type: {$name}");
        }
        
        return $this->pick($this->data[$name]);
    }
    
    public function seed(int $seed): self 
    {
        mt_srand($seed);
        return $this;
    }
    
    public function unique(): self 
    {
        $this->unique = true;
        return $this;
    }

    public function email(): string 
    {
        $name = strtolower($this->firstName());
        $number = mt_rand(1, 999);
        $domain = $this->domain();
        
        return "{$name}{$number}@{$domain}";
    }

    public function username(): string
    {
        return strtolower($this->firstName() . '_' . mt_rand(1, 999));
    }

    public function password(int $length = 12): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        
        return $password;
    }

    public function phone(): string
    {
        return '+1-' . $this->phoneArea() . '-' . mt_rand(100, 999) . '-' . mt_rand(1000, 9999);
    }

    public function url(): string
    {
        return 'https://' . $this->domain() . '/' . $this->slug();
    }

    public function ipv4(): string
    {
        return mt_rand(1, 255) . '.' . mt_rand(0, 255) . '.' . 
               mt_rand(0, 255) . '.' . mt_rand(0, 255);
    }

    public function slug(): string
    {
        return strtolower(str_replace(' ', '-', $this->title()));
    }

    public function address(): string
    {
        return $this->buildingNumber() . ' ' . $this->street() . ', ' . 
               $this->city() . ', ' . $this->country();
    }

    public function uuid(): string
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    public function hash(int $length = 32): string
    {
        return substr(md5(mt_rand()), 0, $length);
    }

    public function token(): string
    {
        return base64_encode($this->hash(32));
    }

    public function pastDate(string $format = 'Y-m-d'): string
    {
        $timestamp = time() - mt_rand(1, 365 * 24 * 60 * 60);
        return date($format, $timestamp);
    }

    public function futureDate(string $format = 'Y-m-d'): string
    {
        $timestamp = time() + mt_rand(1, 365 * 24 * 60 * 60);
        return date($format, $timestamp);
    }

    public function time(): string
    {
        return sprintf('%02d:%02d:%02d', 
            mt_rand(0, 23), mt_rand(0, 59), mt_rand(0, 59));
    }

    // Content Generation
    public function article(string $format = 'text'): string
    {
        $title = $this->pick($this->data['textTitle']);
        $intro = $this->pick($this->data['textIntro']);
        $body = $this->pick($this->data['textBody']);
        $conclusion = $this->pick($this->data['textConclusion']);

        if ($format === 'html') {
            return "<article>" .
                   "<h1>{$title}</h1>" .
                   "<div class=\"intro\"><p>{$intro}</p></div>" .
                   "<div class=\"body\">{$this->formatHtmlParagraphs($body)}</div>" .
                   "<div class=\"conclusion\"><p>{$conclusion}</p></div>" .
                   "</article>";
        }

        return "# {$title}\n\n" .
               "{$intro}\n\n" .
               "{$body}\n\n" .
               "{$conclusion}";
    }

    public function text(string $format = 'text'): string
    {
        $text = $this->pick($this->data['textBody']);
        return $format === 'html' ? $this->formatHtmlParagraphs($text) : $text;
    }

    public function title(string $format = 'text'): string
    {
        $text = $this->pick($this->data['textTitle']);
        return $format === 'html' ? "<h1>{$text}</h1>" : $text;
    }

    public function intro(string $format = 'text'): string
    {
        $text = $this->pick($this->data['textIntro']);
        return $format === 'html' ? "<p class=\"intro\">{$text}</p>" : $text;
    }

    public function conclusion(string $format = 'text'): string
    {
        $text = $this->pick($this->data['textConclusion']);
        return $format === 'html' ? "<p class=\"conclusion\">{$text}</p>" : $text;
    }

    public function markdown(): string
    {
        return "# " . $this->title() . "\n\n" .
               $this->intro() . "\n\n" .
               "## Overview\n\n" .
               $this->text() . "\n\n" .
               "## Details\n\n" .
               $this->text() . "\n\n" .
               "## Summary\n\n" .
               $this->conclusion();
    }

    public function html(): string
    {
        return "<article class=\"content\">" .
               $this->title('html') .
               "<div class=\"content-body\">" .
               $this->intro('html') .
               "<h2>Overview</h2>" .
               $this->text('html') .
               "<h2>Details</h2>" .
               $this->text('html') .
               "<h2>Summary</h2>" .
               $this->conclusion('html') .
               "</div></article>";
    }

    private function formatHtmlParagraphs(string $text): string
    {
        // Split text by double newlines and wrap each paragraph in <p> tags
        $paragraphs = explode("\n\n", $text);
        $html = '';
        foreach ($paragraphs as $p) {
            $html .= "<p>" . trim($p) . "</p>";
        }
        return $html;
    }

    private function pick(array $array): string 
    {
        $value = $array[array_rand($array)];
        
        if ($this->unique) {
            while (in_array($value, $this->used)) {
                $value = $array[array_rand($array)];
            }
            $this->used[] = $value;
        }
        
        return $value;
    }
}
