<?php

namespace Savks\EFilters\Support\Blocks;

use Illuminate\Support\Arr;

class ChooseValue
{
    /**
     * @var string
     */
    public readonly string $id;

    /**
     * @var string
     */
    public readonly string $content;

    /**
     * @var int
     */
    protected int $count;

    /**
     * @var array|null
     */
    protected ?array $payload = null;

    /**
     * @var int|null
     */
    protected ?int $weight = null;

    /**
     * @param string $id
     * @param string $content
     * @param int $count
     */
    public function __construct(string $id, string $content, int $count = 0)
    {
        $this->id = $id;
        $this->content = $content;
        $this->count = $count;
    }

    /**
     * @param int $value
     * @return $this
     */
    public function setWeight(int $value): static
    {
        $this->weight = $value;

        return $this;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function putToPayload(string $key, mixed $value): self
    {
        Arr::set($this->payload, $key, $value);

        return $this;
    }

    /**
     * @param string|null $key
     * @param mixed|null $default
     * @return mixed
     */
    public function payload(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->payload;
        }

        return Arr::get($this->payload, $key, $default);
    }

    /**
     * @param int $count
     * @return $this
     */
    public function updateCount(int $count): static
    {
        $this->count = $count;

        return $this;
    }

    /**
     * @return array
     */
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
