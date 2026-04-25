<?php

namespace App\Data;

abstract class BaseData
{
    /**
     * Create a DTO instance from an associative array.
     */
    public static function fromArray(array $data): static
    {
        return new static(...$data);
    }
}
