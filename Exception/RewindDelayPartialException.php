<?php

declare(strict_types=1);

namespace Wakeapp\Bundle\RabbitQueueBundle\Exception;

use PhpAmqpLib\Message\AMQPMessage;
use RuntimeException;

class RewindDelayPartialException extends RuntimeException
{
    /**
     * @var AMQPMessage[]
     */
    private array $rewindMessageList;
    private int $delay;

    /**
     * @param string $name
     * @param AMQPMessage[] $rewindMessageList
     * @param int $delay
     *
     * @throws RabbitQueueException
     */
    public function __construct(string $name, array $rewindMessageList, int $delay)
    {
        foreach ($rewindMessageList as $rewindMessage) {
            if (!$rewindMessage instanceof AMQPMessage) {
                throw new RabbitQueueException(sprintf('Rewind message must be instance of %s', AMQPMessage::class));
            }
        }

        $this->rewindMessageList = $rewindMessageList;
        $this->delay = $delay;

        parent::__construct(sprintf('Consumer "%s" rewind delay partial messageList', $name));
    }

    /**
     * @return AMQPMessage[]
     */
    public function getRewindMessageList(): array
    {
        return $this->rewindMessageList;
    }

    public function getDelay(): int
    {
        return $this->delay;
    }
}
