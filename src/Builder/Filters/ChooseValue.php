<?php

namespace Savks\EFilters\Builder\Filters;

use Illuminate\Support\Arr;

class ChooseValue
{
    /**
     * @var string
     */
    public string $id;

    /**
     * @var string
     */
    public string $content;

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
    public bool $selected;

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
        $this->selected = false;
    }

    /**
     * @param array $payload
     * @return $this
     */
    public function setPayload(array $payload): self
    {
        $this->payload = $payload;

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
