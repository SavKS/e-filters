<?php

namespace Savks\EFilters\Blocks;

use Closure;
use Illuminate\Support\Arr;

abstract class Block
{
    /**
     * @var string
     */
    public string $id;

    /**
     * @var string
     */
    public string $title;

    /**
     * @var string
     */
    public string $type;

    /**
     * @var string|Closure
     */
    protected string|Closure $mapper;

    /**
     * @var array|null
     */
    protected ?array $payload = null;

    /**
     * @var int
     */
    public int $weight = 9999;

    /**
     * @param string $id
     * @param string $title
     * @param string $type
     * @param string|Closure $mapper
     */
    public function __construct(string $id, string $title, string $type, string|Closure $mapper)
    {
        $this->id = $id;
        $this->title = $title;
        $this->type = $type;
        $this->mapper = $mapper;
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
     * @param bool $flatten
     * @return array
     */
    abstract public function toArray(bool $flatten = false): array;
}
