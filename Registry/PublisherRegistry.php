<?php

declare(strict_types=1);

namespace Wakeapp\Bundle\RabbitQueueBundle\Registry;

use Wakeapp\Bundle\RabbitQueueBundle\Exception\PublisherNotFoundException;
use Wakeapp\Bundle\RabbitQueueBundle\Publisher\AbstractPublisher;
use Symfony\Contracts\Service\ServiceProviderInterface;

use function sprintf;

class PublisherRegistry
{
    private ServiceProviderInterface $publisherRegistry;

    public function __construct(ServiceProviderInterface $publisherRegistry)
    {
        $this->publisherRegistry = $publisherRegistry;
    }

    /**
     * @throws PublisherNotFoundException
     */
    public function getPublisher(int $queueType): AbstractPublisher
    {
        $queueTypeId = (string) $queueType;

        if ($this->publisherRegistry->has($queueTypeId)) {
            return $this->publisherRegistry->get($queueTypeId);
        }

        throw new PublisherNotFoundException(sprintf('Publisher for queue type "%s" not found', $queueType));
    }
}
