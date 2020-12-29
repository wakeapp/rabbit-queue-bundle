Rabbit Queue Bundle
======================

Введение
--------

Бандл предоставляет реализацию по работе с очередями посредством механизма `consumer`'ов на основе `RabbitMQ`.

Установка
---------

### Шаг 1: Загрузка бандла

Откройте консоль и, перейдя в директорию проекта, выполните следующую команду для загрузки наиболее подходящей
стабильной версии этого бандла:
```bash
    composer require wakeapp/rabbit-queue-bundle
```
*Эта команда подразумевает что [Composer](https://getcomposer.org) установлен и доступен глобально.*

### Шаг 2: Подключение бандла

Необходимо включить бандл добавив его в список зарегистрированных бандлов в `app/AppKernel.php` файл вашего проекта:

```php
<?php
// app/AppKernel.php

class AppKernel extends Kernel
{
    // ...

    public function registerBundles()
    {
        $bundles = [
            // ...

            new Wakeapp\Bundle\RabbitQueueBundle\WakeappRabbitQueueBundle(),
        ];

        return $bundles;
    }

    // ...
}
```

Конфигурация
------------

Чтобы начать использовать бандл, необходимо описать конфигурацию подключения к `RabbitMQ`.

```yaml
# app/config.yml
wakeapp_rabbit_queue:
    connections:
        default:
            host: 'rabbitmq'              # хост для подключения к rabbitMQ
            port: 5672                    # порт для подключения к rabbitMQ
            username: 'rabbitmq_user'     # логин для подключения к rabbitMQ
            password: 'rabbitmq_password' # пароль для подключения к rabbitMQ
            vhost: 'example_vhost'        # виртуальный хост для подключения (необязательный параметр)
```

Описание компонентов
-------------
### Producer
`Producer` - используется для отправки сообщений в очередь. 

Для этих целей в бандле реализован [RabbitMqProducer](../Producer/RabbitMqProducer.php), 
с помощью которого можно отправлять сообщения в очередь с заданными параметрами.
```php
<?php
$data = ['message' => 'example'];

/** @var \Wakeapp\Bundle\RabbitQueueBundle\Producer\RabbitMqProducer $producer*/
$producer->put('queue_name', $data);
```

### Consumer
`Consumer` - Используется для получения и обработки сообщений из очереди.

Для реализации логики обработки сообщений необходимо создать класс `consumer`, 
реализующий [ConsumerInterface](../Consumer/ConsumerInterface.php), 
либо наследующий [AbstractConsumer](../Consumer/AbstractConsumer.php), который содержит предустановленные значения для некоторых методов.

### Definition
RabbitMQ позволяет создавать сложные схемы очередей, состоящие из несколько взаимосвязанных `exchange` и `queue`.

Для удобства работы со схемами бандл предоставляет возможность сохранения схем очередей в специальные классы `Definition`, 
которые реализуют [DefinitionInterface](../Definition/DefinitionInterface.php).


Использование
-------------

### Шаг 1: Создание схемы очереди (Definition)
Для инициализации схемы, требуется создать класс Definition, 
который реализует [DefinitionInterface](../Definition/DefinitionInterface.php).
В методе `init` нужно объявить структуру очереди состоящию из необходимых `exchanges`, `queue` и `bindings` 
с помощью стандартных методов работы с каналом [php-amqplib](https://github.com/php-amqplib/php-amqplib). 

```php
<?php

declare(strict_types=1);

namespace Wakeapp\Bundle\RabbitQueueBundle\Definition;

use Wakeapp\Bundle\RabbitQueueBundle\Enum\QueueEnum;
use Wakeapp\Bundle\RabbitQueueBundle\Enum\QueueTypeEnum;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class ExampleFifoDefinition implements DefinitionInterface
{
    public const QUEUE_NAME = QueueEnum::EXAMPLE_FIFO;
    public const ENTRY_POINT = self::QUEUE_NAME;

    /**
     * {@inheritDoc}
     */
    public function init(AMQPStreamConnection $connection): void
    {
        $channel = $connection->channel();

        $channel->queue_declare(
            self::ENTRY_POINT,
            false,
            true,
            false,
            false
        );
    }

    /**
     *
     * {@inheritDoc}
     */
    public function getEntryPointName(): string
    {
        return self::ENTRY_POINT;
    }

    /**
     * {@inheritDoc}
     */
    public function getQueueType(): int
    {
        return QueueTypeEnum::FIFO;
    }

    /**
     * {@inheritDoc}
     */
    public static function getQueueName(): string
    {
        return self::QUEUE_NAME;
    }
}
```

### Шаг 2: Создание consumer'а

Далее необходимо создать класс-`consumer`, наследующий [AbstractConsumer](../Consumer/AbstractConsumer.php).
А в методе `process` реализовать обработку полученных сообщений.

```php
<?php

declare(strict_types=1);

namespace Acme\AppBundle\Consumer;

use Wakeapp\Bundle\RabbitQueueBundle\Consumer\AbstractConsumer;

class ExampleConsumer extends AbstractConsumer
{
    /**
     * {@inheritDoc}
     */
    public function process(array $messageList): void
    {
        foreach ($messageList as $item) {
            // handle some task by specific logic
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getBindQueueName(): string
    {
        return 'example';
    }

    /**
     * {@inheritDoc}
     */
    public static function getName(): string
    {
        return 'example';
    }
}
```

Если в проекте не работает механизм `autowire`, то вам понадобится зарегистрировать `consumer`
с тегом `wakeapp_rabbit_queue.consumer` и опционально добавить приоритет(по умолчанию `priority: "0"`):

```yaml
services:
    app.acme.consumer:
        class:      Acme\AppBundle\Consumer\ExampleConsumer
        tags:
            - { name: wakeapp_rabbit_queue.consumer , priority: "0" }
```

### Шаг 3: Загрузка схем очередей RabbitMQ

Чтобы загрузить схемы `definition` в RabbitMQ необходимо выполнить команду `rabbit:definition:update`. 
Данная команда обновит схему в соответствии с существующими классами `Definition`, реализующими [DefinitionInterface](../Definition/DefinitionInterface.php).

```bash
php bin/console rabbit:definition:update
```

### Шаг 4: Запуск consumer'а

Чтобы запустить `consumer` необходимо выполнить команду `wakeapp:consumer:run`. 
Для запуска нужно передать имя конкретного `consumer`. 

Запуск ранее описанного `consumer`'а будет выглядеть так:

```bash
php bin/console wakeapp:consumer:run example
```

Для просмотра списка всех зарегистрированных `consumer`'ов достаточно выполнить команду `rabbit:consumer:list`.

```text
 Total consumers count: 3
+--------------------+------------+
| Queue Name         | Batch Size |
+--------------------+------------+
| prepend_event_push | 1          |
| send_event_push    | 100        |
| send_regular_push  | 100        |
+--------------------+------------+
```
Лицензия
--------

![license](https://img.shields.io/badge/License-proprietary-red.svg?style=flat-square)
