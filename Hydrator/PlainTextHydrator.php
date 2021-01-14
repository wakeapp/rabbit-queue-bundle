<?php

declare(strict_types=1);

namespace Wakeapp\Bundle\RabbitQueueBundle\Hydrator;

class PlainTextHydrator implements HydratorInterface
{
    public const KEY = 'text/plain';

    public function hydrate(string $dataString): string
    {
        return $dataString;
    }

    /**
     * {@inheritDoc}
     */
    public function dehydrate($data): string
    {
        return $data;
    }

    public static function getKey(): string
    {
        return static::KEY;
    }
}
