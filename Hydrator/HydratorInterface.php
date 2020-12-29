<?php

declare(strict_types=1);

namespace Wakeapp\Bundle\RabbitQueueBundle\Hydrator;

use JsonSerializable;

interface HydratorInterface
{
    public const TAG = 'wakeapp_rabbit_queue.hydrator';

    public function hydrate(string $dataString);

    /**
     * @param JsonSerializable|array|integer|string|null|float $data
     *
     * @return string
     */
    public function dehydrate($data): string;

    public static function getKey(): string;
}
