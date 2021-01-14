<?php

declare(strict_types=1);

namespace Wakeapp\Bundle\RabbitQueueBundle\Exception;

use PhpAmqpLib\Message\AMQPMessage;
use RuntimeException;

use function sprintf;

class ReleasePartialException extends RuntimeException
{
    /**
     * @var AMQPMessage[]
     */
    private array $releaseMessageList;

    /**
     * @param string $name
     * @param AMQPMessage[] $releaseMessageList
     *
     * @throws RabbitQueueException
     */
    public function __construct(string $name, array $releaseMessageList)
    {
        foreach ($releaseMessageList as $releaseMessage) {
            if (!$releaseMessage instanceof AMQPMessage) {
                throw new RabbitQueueException(sprintf('Release message must be instance of %s', AMQPMessage::class));
            }
        }

        $this->releaseMessageList = $releaseMessageList;

        parent::__construct(sprintf('Consumer "%s" release partial messageList', $name));
    }

    /**
     * @return AMQPMessage[]
     */
    public function getReleaseMessageList(): array
    {
        return $this->releaseMessageList;
    }
}
