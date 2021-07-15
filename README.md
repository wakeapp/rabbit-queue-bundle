Rabbit Queue Bundle
======================

# WARNING: This repo is no longer actively maintained

Введение
--------

Бандл предоставляет инструменты по работе с очередями `RabbitMQ` посредством механизма `producer` - `consumer`.

Содержание
--------
1. [Требования](#требования)
2. [Установка](#установка)
    - [Загрузка бандла](#шаг-1-загрузка-бандла)
    - [Подключение бандла](#шаг-2-подключение-бандла)
3. [Конфигурация](#конфигурация)
4. [Описание компонентов](#описание-компонентов)
    - [Producer](#producer)
    - [Publisher](#publisher)
    - [Consumer](#consumer)
    - [Hydrator](#hydrator)
    - [Definition](#definition)
5. [Доступные команды](#доступные-команды)
6. [Использование](#использование)
    - [Шаг 1: Создание схемы очереди (Definition)](#шаг-1-создание-схемы-очереди-definition)
    - [Шаг 2: Создание consumer'а](#шаг-2-создание-consumerа)
    - [Шаг 3: Загрузка схем очередей RabbitMQ](#шаг-3-загрузка-схем-очередей-rabbitmq)
    - [Шаг 4: Запуск consumer'а](#шаг-4-запуск-consumerа)
7. [Лицензия](#лицензия)

Требования
---------

Для корректной работы бандла требуется подключить следующие плагины RabbitMQ:
 - [RabbitMQ Message Deduplication Plugin](https://github.com/noxdafox/rabbitmq-message-deduplication)
 - [RabbitMQ Delayed Message Plugin](https://github.com/rabbitmq/rabbitmq-delayed-message-exchange)

Установка
---------

### Шаг 1: Загрузка бандла

В директории проекта, выполните следующую команду для загрузки наиболее подходящей
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
# app/packages/wakeapp_rabbit_queue.yaml
wakeapp_rabbit_queue:
    connections:
        default:
            host: 'rabbitmq'              # хост для подключения к rabbitMQ
            port: 5672                    # порт для подключения к rabbitMQ
            username: 'rabbitmq_user'     # логин для подключения к rabbitMQ
            password: 'rabbitmq_password' # пароль для подключения к rabbitMQ
            vhost: 'example_vhost'        # виртуальный хост для подключения (необязательный параметр)
            connection_timeout: 3         # таймаут соединения
            read_write_timeout: 3         # таймаут на чтение/запись
            heartbeat: 0                  # частота heartbeat
    consumer:
      wait_timeout: 3                     # таймаут ожидания новых сообщений для обработки пачки в секундах (по умолчанию 3)
      idle_timeout: 0                     # таймаут ожидания сообщений в пустой очереди в секундах (по умолчанию 0 - нет таймаута)
```

Описание компонентов
-------------
### Producer
`Producer` - используется для отправки сообщений в очередь. 

Для этих целей в бандле реализован [RabbitMqProducer](Producer/RabbitMqProducer.php), 
с помощью которого можно отправлять сообщения в очередь с заданными параметрами.
```php
<?php
$data = ['message' => 'example']; # Сообщение
$options = ['key' => 'unique_key', 'delay' => 1000]; # Опции, в зависимости от типа очереди

/** @var \Wakeapp\Bundle\RabbitQueueBundle\Producer\RabbitMqProducer $producer */
$producer->put('queue_name', $data, $options);
```

### Publisher
Публикация сообщений в очередь происходит с помощью специальных классов паблишеров.
`Producer` определяет какой паблишер использовать для публикации по типу очереди, с которым связан паблишер.

Соответственно на каждый новый тип очереди требуется свой класс `Publisher` с кастомной логикой обработки/валидации и публикации сообщений в канал. 

Бандл поддерживает следующие типы очередей:
 - FIFO
 - Delay
 - Deduplicate
 - Deduplicate + Delay

При желании добавить собственный тип очереди, необходимо создать класс `Publisher` наследующий [AbstractPublisher](Publisher/AbstractPublisher.php) или реализующий [PublisherInterface](Publisher/PublisherInterface.php).

Пример DelayPublisher:
```php
<?php

declare(strict_types=1);

namespace Wakeapp\Bundle\RabbitQueueBundle\Publisher;

use Wakeapp\Bundle\RabbitQueueBundle\Enum\QueueHeaderOptionEnum;
use Wakeapp\Bundle\RabbitQueueBundle\Definition\DefinitionInterface;
use Wakeapp\Bundle\RabbitQueueBundle\Enum\QueueOptionEnum;
use Wakeapp\Bundle\RabbitQueueBundle\Enum\QueueTypeEnum;
use Wakeapp\Bundle\RabbitQueueBundle\Exception\RabbitQueueException;

use function is_int;
use function sprintf;

class DelayPublisher extends AbstractPublisher
{
    public const QUEUE_TYPE = QueueTypeEnum::FIFO | QueueTypeEnum::DELAY;
    
    /**
    * Custom prepare options logic
    */
    protected function prepareOptions(DefinitionInterface $definition, array $options): array
    {
        $delay = $options[QueueOptionEnum::DELAY] ?? null;

        if (!is_int($delay)) {
            $message = sprintf(
                'Element for queue "%s" must be with option %s. See %s',
                $definition::getQueueName(),
                QueueOptionEnum::DELAY,
                QueueOptionEnum::class
            );

            throw new RabbitQueueException($message);
        }

        $amqpTableOption[QueueHeaderOptionEnum::X_DELAY] = $delay * 1000;

        return $amqpTableOption;
    }

    /**
    * Queue type supported by publisher
    */
    public static function getQueueType(): string
    {
        return (string) self::QUEUE_TYPE;
    }
}
```

### Consumer
`Consumer` - Используется для получения и обработки сообщений из очереди.

Для реализации логики обработки сообщений необходимо создать класс `consumer`, 
реализующий [ConsumerInterface](Consumer/ConsumerInterface.php), 
либо наследующий [AbstractConsumer](Consumer/AbstractConsumer.php), который содержит предустановленные значения для некоторых методов.

```php
<?php

declare(strict_types=1);

namespace Acme\AppBundle\Consumer;

use Wakeapp\Bundle\RabbitQueueBundle\Consumer\AbstractConsumer;

class ExampleConsumer extends AbstractConsumer
{
    public const DEFAULT_BATCH_SIZE = 100; # Размер пачки

    /**
     * {@inheritDoc}
     */
    public function process(array $messageList): void
    {
        foreach ($messageList as $item) {
            $data = $this->decodeMessageBody($item); # Decode message by hydrator

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
В методе `process()` необходимо реализовать обработку полученных сообщений. 
Сообщения поступают пачками, размер которых задается константой `DEFAULT_BATCH_SIZE` (по умолчанию = 1).

__Сумма `DEFAULT_BATCH_SIZE` со всех потребителей одной очереди не должна превышать значения 65535__.

### Hydrator
Для удобства работы с сообщениями разных форматов бандл предоставляет инструменты гидрации (кодирование/декодирование сообщений в необходимый формат).

По умолчанию доступны следующие гидраторы:
 - JsonHydrator - для работы с сообщениями в формате json (_используется по умолчанию_).
 - PlainTextHydrator - для работы с простыми текстовыми сообщениями.
 
Также существует возможность создания собственного гидратора. 
Для этого необходимо реализовать [HydratorInterface](Hydrator/HydratorInterface.php) и изменить параметр конфигурации `hydrator_name` на тип нового гидратора.

### Definition
RabbitMQ позволяет создавать сложные схемы очередей, состоящие из несколько взаимосвязанных `exchange` и `queue`.

Для удобства работы со схемами бандл предоставляет возможность сохранения схем очередей в специальные классы `Definition`, 
которые реализуют [DefinitionInterface](Definition/DefinitionInterface.php).

Пример FIFO:
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

Пример delay + deduplicate:
```php
<?php

declare(strict_types=1);

namespace Wakeapp\Bundle\RabbitQueueBundle\Definition;

use Wakeapp\Bundle\RabbitQueueBundle\Enum\QueueEnum;
use Wakeapp\Bundle\RabbitQueueBundle\Enum\QueueTypeEnum;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Wire\AMQPTable;

class ExampleDeduplicateDelayDefinition implements DefinitionInterface
{
    public const QUEUE_NAME = QueueEnum::EXAMPLE_DEDUPLICATE_DELAY;
    public const ENTRY_POINT = self::QUEUE_NAME . '@exchange_deduplication';

    private const SECOND_POINT = self::QUEUE_NAME . '@exchange_delay';
    private const THIRD_POINT = self::QUEUE_NAME;

    /**
     * {@inheritDoc}
     */
    public function init(AMQPStreamConnection $connection): void
    {
        $channel = $connection->channel();

        $channel->exchange_declare(
            self::ENTRY_POINT,
            'x-message-deduplication',
            false,
            true,
            false,
            false,
            false,
            new AMQPTable(['x-cache-size' => 1_000_000_000])
        );

        $channel->exchange_declare(
            self::SECOND_POINT,
            'x-delayed-message',
            false,
            true,
            false,
            false,
            false,
            new AMQPTable(['x-delayed-type' => AMQPExchangeType::DIRECT])
        );

        $channel->queue_declare(
            self::THIRD_POINT,
            false,
            true,
            false,
            false
        );

        $channel->exchange_bind(self::SECOND_POINT, self::ENTRY_POINT);
        $channel->queue_bind(self::THIRD_POINT, self::SECOND_POINT);
    }

    /**
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
        return QueueTypeEnum::FIFO | QueueTypeEnum::DEDUPLICATE | QueueTypeEnum::DELAY;
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

В методе `init()` объявляется структура очереди состоящая из необходимых `exchanges`, `queue` и `bindings` 
с помощью стандартных методов [php-amqplib](https://github.com/php-amqplib/php-amqplib). 

Метод `getEntryPointName()` - отвечает за точку входа сообщений. Точкой входа может быть название `exchange` или `queue` в зависимости от структуры схемы.

Метод `getQueueName()` - название очереди, куда в конечном итоге попадут сообщения.

Жизненный цикл сообщения:
```text
Сообщение -> Producer -> EntryPoint -> Структура очереди exchanges, bindings -> Queue -> Consumer
```

Таким образом `producer` отправляет сообщения на точку входа, а `consumer` забирает сообщения из очереди. 

В простейшем случае при использовании обычной очереди FIFO, точкой входа будет являться название очереди.

Доступные команды
-------------
1. `rabbit:consumer:run` - запускает выбранный консьюмер.
```bash
php bin/console rabbit:consumer:run <name> # <name> - название консьюмера.
```

2. `rabbit:definition:update` - загружает все схемы очередей `RabbitMQ` в соответствии с существующими классами `Definition`.

*Примечание: Данная команда не обновляет существующие схемы.*
```bash
php bin/console rabbit:definition:update
```

3. `rabbit:consumer:list` - выводит список консьюмеров, зарегистрированных в проекте.
```bash
php bin/console rabbit:consumer:list
```
Пример вывода команды:
```text
 Total consumers count: 2
+--------------------+------------+
| Queue Name         | Batch Size |
+--------------------+------------+
| example_first      | 1          |
| example_second     | 100        |
+--------------------+------------+
```

Использование
-------------

### Шаг 1: Создание схемы очереди (Definition)
Для инициализации схемы, требуется создать класс Definition, 
который реализует [DefinitionInterface](Definition/DefinitionInterface.php).
В методе `init` нужно объявить структуру очереди состоящию из необходимых `exchanges`, `queue` и `bindings` 
с помощью стандартных методов работы с каналом [php-amqplib](https://github.com/php-amqplib/php-amqplib). 

[Пример создания Definition](#definition)

### Шаг 2: Создание consumer'а

Далее необходимо создать класс-`consumer`, наследующий [AbstractConsumer](Consumer/AbstractConsumer.php).
А в методе `process` реализовать обработку полученных сообщений.

[Пример создания Consumer](#consumer)

Если в проекте не работает механизм `autowire`, то вам понадобится зарегистрировать `consumer`
с тегом `wakeapp_rabbit_queue.consumer`:

```yaml
services:
    app.acme.consumer:
        class:      Acme\AppBundle\Consumer\ExampleConsumer
        tags:
            - { name: wakeapp_rabbit_queue.consumer }
```

### Шаг 3: Загрузка схем очередей RabbitMQ

Чтобы загрузить схемы `definition` в RabbitMQ необходимо выполнить команду `rabbit:definition:update`. 
Данная команда обновит схему в соответствии с существующими классами `Definition`, реализующими [DefinitionInterface](Definition/DefinitionInterface.php).

```bash
php bin/console rabbit:definition:update
```

### Шаг 4: Запуск consumer'а

Чтобы запустить `consumer` необходимо выполнить команду `rabbit:consumer:run` rabbit. 
Для запуска нужно передать имя конкретного `consumer`. 

Запуск ранее описанного `consumer`'а будет выглядеть так:

```bash
php bin/console rabbit:consumer:run example
```

Для просмотра списка всех зарегистрированных `consumer`'ов достаточно выполнить команду `rabbit:consumer:list`.

Лицензия
--------

[![license](https://img.shields.io/badge/License-MIT-green.svg?style=flat-square)](./LICENSE)
