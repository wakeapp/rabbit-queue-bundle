<?php

declare(strict_types=1);

namespace Wakeapp\Bundle\RabbitQueueBundle\Enum;

class ExchangeEnum
{
    public const RETRY_EXCHANGE = 'retry@exchange_delay';
}
