<?php

declare(strict_types=1);

namespace Wakeapp\Bundle\RabbitQueueBundle\Producer;

interface RabbitMqProducerInterface
{
    public function put(string $queueName, $data, array $options = []);
}
