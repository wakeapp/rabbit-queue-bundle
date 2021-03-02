<?php

declare(strict_types=1);

namespace Wakeapp\Bundle\RabbitQueueBundle\Exception;

use RuntimeException;

class RewindDelayPartialException extends RuntimeException
{
    /**
     * @var int[]
     */
    private array $rewindDeliveryTagList;
    private int $delay;

    /**
     * @param int[] $rewindDeliveryTagList
     * @param int $delay
     *
     * @throws RabbitQueueException
     */
    public function __construct(array $rewindDeliveryTagList, int $delay)
    {
        foreach ($rewindDeliveryTagList as $deliveryTag) {
            if (!is_int($deliveryTag)) {
                throw new RabbitQueueException('Delivery tag must be integer');
            }
        }

        $this->rewindDeliveryTagList = $rewindDeliveryTagList;
        $this->delay = $delay;

        parent::__construct('Consumer rewind delay partial messageList');
    }

    /**
     * @return int[]
     */
    public function getRewindDeliveryTagList(): array
    {
        return $this->rewindDeliveryTagList;
    }

    public function getDelay(): int
    {
        return $this->delay;
    }
}
