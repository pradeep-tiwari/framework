<?php

namespace Lightpack\Faker;

/**
 * Faker - A lightweight fake data generator
 * 
 * @method string firstName()     Returns a random first name
 * @method string lastName()      Returns a random last name
 * @method string company()       Returns a random company name
 * @method string jobTitle()      Returns a random job title
 * @method string domain()        Returns a random domain name
 * @method string tld()          Returns a random top-level domain
 * @method string city()         Returns a random city name
 * @method string country()      Returns a random country name
 * @method string street()       Returns a random street name
 * @method string buildingNumber() Returns a random building number
 * @method string phoneArea()    Returns a random phone area code
 * @method string word()         Returns a random word
 * 
 * @method self seed(int $seed)  Set a seed for reproducible results
 * @method self unique()         Ensure values are unique
 * 
 * Methods with special formatting:
 * @method string email()        Returns a random email address
 * @method string username()     Returns a random username
 * @method string password(int $length = 12) Returns a random password
 * @method string phone()        Returns a random phone number
 * @method string url()          Returns a random URL
 * @method string ipv4()         Returns a random IPv4 address
 * @method string slug()         Returns a URL-friendly slug
 * @method string address()      Returns a full address
 * @method string text(int $words = 10) Returns random text
 * @method string paragraph(int $sentences = 3) Returns random paragraphs
 * @method string title()        Returns a random title
 * @method string markdown()     Returns random markdown text
 * @method string uuid()         Returns a UUID v4
 * @method string hash(int $length = 32) Returns a random hash
 * @method string token()        Returns a random token
 * @method string pastDate(string $format = 'Y-m-d') Returns a past date
 * @method string futureDate(string $format = 'Y-m-d') Returns a future date
 * @method string time()         Returns a random time
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
        $words = [];
        for ($i = 0; $i < mt_rand(3, 6); $i++) {
            $words[] = $this->word();
        }
        return strtolower(str_replace(' ', '-', implode('-', $words)));
    }

    public function address(): string
    {
        return $this->buildingNumber() . ' ' . $this->street() . ', ' . 
               $this->city() . ', ' . $this->country();
    }

    public function text(int $words = 10): string 
    {
        $text = [];
        for ($i = 0; $i < $words; $i++) {
            $text[] = $this->word();
        }
        return ucfirst(implode(' ', $text)) . '.';
    }

    public function paragraph(int $sentences = 3): string
    {
        $paragraph = [];
        for ($i = 0; $i < $sentences; $i++) {
            $paragraph[] = $this->text(mt_rand(5, 10));
        }
        return implode(' ', $paragraph);
    }

    public function title(): string
    {
        return ucwords($this->text(mt_rand(3, 6)));
    }

    public function markdown(): string
    {
        return "# " . $this->title() . "\n\n" .
               $this->paragraph() . "\n\n" .
               "## " . $this->title() . "\n\n" .
               "* " . $this->text() . "\n" .
               "* " . $this->text() . "\n" .
               "* " . $this->text() . "\n";
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
