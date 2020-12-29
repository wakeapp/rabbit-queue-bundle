<?php

declare(strict_types=1);

namespace Wakeapp\Bundle\RabbitQueueBundle\Enum;

class QueueOptionEnum
{
    /**
     * Delay in seconds when message will be delivering to queue.
     */
    public const DELAY = 'delay';

    /**
     * Key for grouping messages.
     */
    public const KEY = 'key';
}
