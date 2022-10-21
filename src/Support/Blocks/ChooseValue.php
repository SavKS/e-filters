<?php

namespace Savks\EFilters\Support\Blocks;

use Illuminate\Support\Arr;

class ChooseValue
{
    /**
     * @var array<string, mixed>|null
     */
    protected ?array $payload = null;

    protected ?int $weight = null;

    public function __construct(
        public readonly string $id,
        public readonly string $content,
        protected int $count = 0
    ) {
    }

    public function setWeight(int $value): static
    {
        $this->weight = $value;

        return $this;
    }

    public function putToPayload(string $key, mixed $value): self
    {
        Arr::set($this->payload, $key, $value);

        return $this;
    }

    public function payload(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->payload;
        }

        return Arr::get($this->payload, $key, $default);
    }

    public function updateCount(int $count): static
    {
        $this->count = $count;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'count' => $this->count,
            'payload' => $this->payload,
            'weight' => $this->weight,
        ];
    }
}
