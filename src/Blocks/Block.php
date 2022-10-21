<?php

namespace Savks\EFilters\Blocks;

use Illuminate\Support\Arr;

abstract class Block
{
    /**
     * @var array<string, mixed>|null
     */
    protected ?array $payload = null;

    protected ?int $weight = null;

    public function __construct(
        public string $id,
        public string $title,
        public string $entityType
    ) {
    }

    public function setWeight(int $value): static
    {
        $this->weight = $value;

        return $this;
    }

    public function putToPayload(string $key, mixed $value): static
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
}
