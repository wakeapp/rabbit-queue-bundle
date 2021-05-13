<?php

declare(strict_types=1);

namespace Wakeapp\Bundle\RabbitQueueBundle\Enum;

class QueueTypeEnum
{
    public const FIFO = 1;
    public const DELAY = 2;
    public const REPLACE = 4;
    public const DEDUPLICATE = 8;
    public const ROUTER = 16;
}
