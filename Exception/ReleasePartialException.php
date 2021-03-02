<?php

declare(strict_types=1);

namespace Wakeapp\Bundle\RabbitQueueBundle\Exception;

use RuntimeException;

use function is_int;

class ReleasePartialException extends RuntimeException
{
    /**
     * @var int[]
     */
    private array $releaseDeliveryTagList;

    /**
     * @param int[] $releaseDeliveryTagList
     *
     * @throws RabbitQueueException
     */
    public function __construct(array $releaseDeliveryTagList)
    {
        foreach ($releaseDeliveryTagList as $releaseMessage) {
            if (!is_int($releaseMessage)) {
                throw new RabbitQueueException('Delivery tag must be integer');
            }
        }

        $this->releaseDeliveryTagList = $releaseDeliveryTagList;

        parent::__construct('Consumer release partial message list');
    }

    /**
     * @return int[]
     */
    public function getReleaseDeliveryTagList(): array
    {
        return $this->releaseDeliveryTagList;
    }
}
