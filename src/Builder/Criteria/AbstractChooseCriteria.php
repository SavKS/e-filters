<?php

namespace Savks\EFilters\Builder\Criteria;

use Illuminate\Support\Arr;
use RuntimeException;

abstract class AbstractChooseCriteria implements CriteriaInterface
{
    /**
     * @var array|null
     */
    public ?array $payload;

    /**
     * @var array
     */
    public array $values;

    /**
     * @var array
     */
    public array $separated;

    /**
     * @var array
     */
    public array $conditions = [];

    /**
     * @param array|null $payload
     * @return void
     */
    public function __construct(array $payload = null)
    {
        $this->payload = $payload;
    }

    /**
     * @param string|null $key
     * @param mixed|null $default
     * @return mixed
     */
    protected function payload(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->payload;
        }

        return Arr::get($this->payload, $key, $default);
    }

    /**
     * @param array $values
     * @return $this
     */
    public function use(array $values): self
    {
        $this->values = $values;

        $this->separated = $this->separate();

        foreach ($this->separated as $item) {
            if (! \is_array($item)) {
                $class = \class_basename(static::class);

                throw new RuntimeException("The method [{$class}::separate] must return an array of arrays");
            }

            if ($item['values']) {
                $this->conditions[] = $this->prepare($item['id'], $item['values']);
            }
        }

        return $this;
    }

    /**
     * @return array
     */
    abstract protected function separate(): array;

    /**
     * @param string $id
     * @param array $values
     * @return Condition
     */
    abstract protected function prepare(string $id, array $values): Condition;
}
