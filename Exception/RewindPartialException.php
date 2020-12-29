<?php

declare(strict_types=1);

namespace Wakeapp\Bundle\RabbitQueueBundle\Exception;

use PhpAmqpLib\Message\AMQPMessage;
use RuntimeException;

class RewindPartialException extends RuntimeException
{
    /**
     * @var AMQPMessage[]
     */
    private array $rewindMessageList;

    /**
     * @param string $name
     * @param AMQPMessage[] $rewindMessageList
     *
     * @throws RabbitQueueException
     */
    public function __construct(string $name, array $rewindMessageList)
    {
        foreach ($rewindMessageList as $rewindMessage) {
            if (!$rewindMessage instanceof AMQPMessage) {
                throw new RabbitQueueException(sprintf('Rewind message must be instance of %s', AMQPMessage::class));
            }
        }

        $this->rewindMessageList = $rewindMessageList;

        parent::__construct(sprintf('Consumer "%s" rewind partial messageList', $name));
    }

    /**
     * @return AMQPMessage[]
     */
    public function getRewindMessageList(): array
    {
        return $this->rewindMessageList;
    }
}
