<?php

namespace Savks\EFilters\Builder\Filters;

use Closure;
use Illuminate\Support\Arr;

abstract class AbstractBlock
{
    /**
     * @var string
     */
    public string $id;

    /**
     * @var string
     */
    public string $name;

    /**
     * @var string
     */
    public string $entity;

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
     * @param string $name
     * @param string $entity
     * @param string|Closure $mapper
     */
    public function __construct(string $id, string $name, string $entity, string|Closure $mapper)
    {
        $this->id = $id;
        $this->name = $name;
        $this->entity = $entity;
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
     * @param bool $flatten
     * @return array
     */
    abstract public function toArray(bool $flatten = false): array;
}
