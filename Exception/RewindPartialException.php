<?php

declare(strict_types=1);

namespace Wakeapp\Bundle\RabbitQueueBundle\Exception;

use RuntimeException;

use function is_int;

class RewindPartialException extends RuntimeException
{
    /**
     * @var int[]
     */
    private array $rewindDeliveryTagList;

    /**
     * @param int[] $rewindDeliveryTagList
     *
     * @throws RabbitQueueException
     */
    public function __construct(array $rewindDeliveryTagList)
    {
        foreach ($rewindDeliveryTagList as $deliveryTag) {
            if (!is_int($deliveryTag)) {
                throw new RabbitQueueException('Delivery tag must be integer');
            }
        }

        $this->rewindDeliveryTagList = $rewindDeliveryTagList;

        parent::__construct('Consumer rewind partial message list');
    }

    /**
     * @return int[]
     */
    public function getRewindDeliveryTagList(): array
    {
        return $this->rewindDeliveryTagList;
    }
}
