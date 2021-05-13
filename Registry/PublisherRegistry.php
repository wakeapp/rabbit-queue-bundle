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
        if ($this->publisherRegistry->has((string) $queueType)) {
            return $this->publisherRegistry->get((string) $queueType);
        }

        throw new PublisherNotFoundException(sprintf('Publisher for queue type "%s" not found', $queueType));
    }
}
