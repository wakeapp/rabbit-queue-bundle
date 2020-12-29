Rabbit Queue Bundle
======================

Введение
--------

Бандл предоставляет инструменты по работе с очередями `RabbitMQ` посредством механизма `producer` - `consumer`.

Содержание
--------

1. [Установка](#установка)
    - [Загрузка бандла](#шаг-1-загрузка-бандла)
    - [Подключение бандла](#шаг-2-подключение-бандла)
2. [Конфигурация](#конфигурация)
3. [Описание компонентов](#описание-компонентов)
    - [Producer](#producer)
    - [Consumer](#consumer)
    - [Definition](#definition)
4. [Доступные команды](#доступные-команды)
5. [Использование](#использование)
    - [Шаг 1: Создание схемы очереди (Definition)](#шаг-1-создание-схемы-очереди-definition)
    - [Шаг 2: Создание consumer'а](#шаг-2-создание-consumerа)
    - [Шаг 3: Загрузка схем очередей RabbitMQ](#шаг-3-загрузка-схем-очередей-rabbitmq)
    - [Шаг 4: Запуск consumer'а](#шаг-4-запуск-consumerа)
6. [Лицензия](#лицензия)

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

Для этих целей в бандле реализован [RabbitMqProducer](Producer/RabbitMqProducer.php), 
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
реализующий [ConsumerInterface](Consumer/ConsumerInterface.php), 
либо наследующий [AbstractConsumer](Consumer/AbstractConsumer.php), который содержит предустановленные значения для некоторых методов.

### Definition
RabbitMQ позволяет создавать сложные схемы очередей, состоящие из несколько взаимосвязанных `exchange` и `queue`.

Для удобства работы со схемами бандл предоставляет возможность сохранения схем очередей в специальные классы `Definition`, 
которые реализуют [DefinitionInterface](Definition/DefinitionInterface.php).

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

Далее необходимо создать класс-`consumer`, наследующий [AbstractConsumer](Consumer/AbstractConsumer.php).
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
Данная команда обновит схему в соответствии с существующими классами `Definition`, реализующими [DefinitionInterface](Definition/DefinitionInterface.php).

```bash
php bin/console rabbit:definition:update
```

### Шаг 4: Запуск consumer'а

Чтобы запустить `consumer` необходимо выполнить команду `rabbit:consumer:run`rabbit. 
Для запуска нужно передать имя конкретного `consumer`. 

Запуск ранее описанного `consumer`'а будет выглядеть так:

```bash
php bin/console rabbit:consumer:run example
```

Для просмотра списка всех зарегистрированных `consumer`'ов достаточно выполнить команду `rabbit:consumer:list`.

Лицензия
--------

![license](https://img.shields.io/badge/License-proprietary-red.svg?style=flat-square)
