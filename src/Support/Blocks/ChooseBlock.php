<?php

namespace Savks\EFilters\Support\Blocks;

use Illuminate\Support\Arr;
use Savks\EFilters\Blocks\Block;

class ChooseBlock extends Block
{
    /**
     * @var ChooseValue[]
     */
    protected array $values = [];

    /**
     * @param ChooseValue $value
     * @return $this
     */
    public function addValue(ChooseValue $value): self
    {
        $this->values[$value->id] = $value;

        return $this;
    }

    /**
     * @param array $aggregated
     */
    public function mapToValues(array $aggregated): void
    {
        $buckets = \is_string($this->mapper) ?
            Arr::get($aggregated, $this->mapper) :
            \call_user_func($this->mapper, $aggregated);

        $buckets = Arr::pluck($buckets, 'doc_count', 'key');

        foreach ($this->values as $value) {
            $count = $buckets[$value->id] ?? 0;

            $value->isActive = (bool)$count;
            $value->count = $count;
        }
    }

    /**
     * @param bool $flatten
     * @return array
     */
    public function toArray(bool $flatten = false): array
    {
        $block = [
            'id' => $this->id,
            'type' => 'choose',
            'name' => $this->title,
            'payload' => $this->payload,
            'weight' => $this->weight,
        ];

        $values = [];

        foreach ($this->values as $value) {
            /** @var ChooseValue $data */
            $data = $value->toArray();

            $values[$data['id']] = [
                'id' => $data['id'],
                'parentId' => $this->id,
                'content' => $data['content'],
                'payload' => $data['payload'],
                'count' => $data['count'],
                'isActive' => $data['isActive'],
            ];
        }

        if ($flatten) {
            $block['valueIds'] = \array_keys($values);

            return [
                'block' => $block,
                'values' => $values,
            ];
        }

        $block['values'] = \array_values($values);

        return [
            'block' => $block,
        ];
    }
}
