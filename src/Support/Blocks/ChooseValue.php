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
    public int $count;

    /**
     * @var bool
     */
    public bool $isActive;

    /**
     * @var bool
     */
    public bool $isSelected;

    /**
     * @var array|null
     */
    protected ?array $payload = null;

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

        $this->isActive = true;
        $this->isSelected = false;
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
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'count' => $this->count,
            'payload' => $this->payload,
            'isActive' => $this->isActive,
        ];
    }
}
