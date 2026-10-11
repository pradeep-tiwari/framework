<?php

namespace Lightpack\AI\Providers;

use Lightpack\AI\AI;

/**
 * Deterministic AI provider for testing.
 *
 * Responses are scripted via respondWith() and consumed in order; when the
 * script is exhausted the last response repeats. Every generate() call is
 * recorded for later inspection via requests().
 *
 * embed() returns deterministic pseudo-embeddings derived from the input,
 * so embed() + similar() flows are testable end to end without scripting.
 */
class Fake extends AI
{
    private array $responses = [];
    private array $requests = [];
    private int $cursor = 0;

    /**
     * Script provider responses. Each entry is consumed by one generate()
     * call, in order. The last response repeats once the script ends.
     */
    public function respondWith(array $responses): self
    {
        $this->responses = array_values($responses);
        $this->cursor = 0;

        return $this;
    }

    /**
     * Params of every generate()/generateStream() call, in order.
     */
    public function requests(): array
    {
        return $this->requests;
    }

    public function generate(array $params): array
    {
        $this->requests[] = $params;

        return [
            'text' => $this->nextResponse(),
            'finish_reason' => 'stop',
            'usage' => [],
            'raw' => ['fake' => true],
        ];
    }

    public function generateStream(array $params, callable $onChunk): void
    {
        $this->requests[] = $params;

        $onChunk($this->nextResponse());
    }

    /**
     * Deterministic pseudo-embedding: hashes the input into a fixed-length
     * float vector. Same input always produces the same vector, so
     * similar() works meaningfully on fake embeddings.
     */
    protected function generateEmbedding(string|array $input, array $options = []): array
    {
        $this->requests[] = ['type' => 'embed', 'input' => $input];

        $embed = function (string $text): array {
            $vector = [];

            foreach (str_split(md5($text), 2) as $byte) {
                $vector[] = hexdec($byte) / 255;
            }

            return $vector;
        };

        return is_array($input) ? array_map($embed, $input) : $embed($input);
    }

    private function nextResponse(): string
    {
        $text = $this->responses[$this->cursor] ?? end($this->responses);
        $this->cursor++;

        return $text === false ? '' : (string) $text;
    }
}
