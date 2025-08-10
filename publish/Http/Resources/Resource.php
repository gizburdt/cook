<?php

namespace App\Http\Resources;

use Illuminate\Container\Container;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use JsonSerializable;

abstract class Resource extends JsonResource
{
    protected array $only;

    public function only($fields): self
    {
        $this->only = $fields;

        return $this;
    }

    public function resolve($request = null): array
    {
        $data = $this->toCollection(
            $request ?? Container::getInstance()->make('request')
        )->when($this->only, function ($collect) {
            return $collect->only($this->only);
        });

        // Inherited from parent
        if ($data instanceof Arrayable) {
            $data = $data->toArray();
        } elseif ($data instanceof JsonSerializable) {
            $data = $data->jsonSerialize();
        }

        return $this->filter((array) $data);
    }

    protected function toCollection($payload): Collection
    {
        return collect($this->toArray($payload));
    }
}
