<?php

declare(strict_types=1);

namespace Wakeapp\Bundle\RabbitQueueBundle\Hydrator;

use Wakeapp\Bundle\RabbitQueueBundle\Exception\RabbitQueueException;
use JsonException;

use const JSON_THROW_ON_ERROR;

use function json_encode;

class JsonHydrator implements HydratorInterface
{
    public const KEY = 'application/json';

    /**
     * @throws RabbitQueueException
     */
    public function hydrate(string $dataString)
    {
        try {
            return json_decode($dataString, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RabbitQueueException('Invalid hydrate data', 1, $exception);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @throws RabbitQueueException
     */
    public function dehydrate($data): string
    {
        try {
            return json_encode($data, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RabbitQueueException('Invalid dehydrate data', 1, $exception);
        }
    }

    public static function getKey(): string
    {
        return static::KEY;
    }
}
