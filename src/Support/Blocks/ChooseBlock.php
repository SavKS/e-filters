<?php

namespace Savks\EFilters\Support\Blocks;

use Closure;
use Savks\EFilters\Blocks\Block;

class ChooseBlock extends Block
{
    protected Closure $countsMapResolver;

    /**
     * @var ChooseValue[]
     */
    protected array $values = [];

    /**
     * @param Closure(array): array<string, int> $countsMapResolver
     */
    public function __construct(string $id, string $title, string $entityType, Closure $countsMapResolver)
    {
        parent::__construct($id, $title, $entityType);

        $this->countsMapResolver = $countsMapResolver;
    }

    public function addValue(ChooseValue $value): self
    {
        $this->values[] = $value;

        return $this;
    }

    public function updateValueCounts(array $aggregated): void
    {
        $countsMap = \call_user_func($this->countsMapResolver, $aggregated);

        foreach ($this->values as $value) {
            $value->updateCount($countsMap[$value->id] ?? 0);
        }
    }

    public function toArray(bool $flatten = false): array
    {
        $mappedBlock = [
            'id' => $this->id,
            'type' => 'choose',
            'entityType' => $this->entityType,
            'title' => $this->title,
            'payload' => $this->payload,
            'weight' => $this->weight,
        ];

        $mappedValues = [];

        foreach ($this->values as $value) {
            $data = $value->toArray();

            $mappedValues[$data['id']] = [
                ...$data,

                'parentId' => $this->id,
            ];
        }

        $sortedMappedValues = \collect($mappedValues)->sortBy(
            fn (array $mappedValue) => $mappedValue['weight'] ?? $mappedValue['content']
        )->all();

        if ($flatten) {
            return [
                [
                    ...$mappedBlock,

                    'valueIds' => \array_keys($sortedMappedValues),
                ],
                $sortedMappedValues,
            ];
        }

        return [
            ...$mappedBlock,

            'values' => \array_values($sortedMappedValues),
        ];
    }
}
