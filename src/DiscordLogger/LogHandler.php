<?php

namespace Bmohsen\DiscordLogger;

use Bmohsen\DiscordLogger\Contracts\DiscordWebHook;
use Bmohsen\DiscordLogger\Contracts\RecordToMessage;
use Bmohsen\DiscordLogger\Converters\SimpleRecordConverter;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger as Monolog;
use Monolog\LogRecord;
use RuntimeException;
use function class_implements;

class LogHandler extends AbstractProcessingHandler
{
    /** @var DiscordWebHook */
    private $discord;

    /** @var RecordToMessage */
    private $recordToMessage;

    /** @throws BindingResolutionException */
    public function __construct(Container $container, Repository $config, array $channelConfig)
    {
        parent::__construct(Monolog::toMonologLevel($channelConfig['level'] ?? Monolog::DEBUG));

        $this->discord = $container->make(DiscordWebHook::class, ['url' => $channelConfig['url']]);
        $this->recordToMessage = $this->createRecordConverter($container, $config);
    }

    public function write(LogRecord $record): void
    {
        $record = $record->toArray();
        foreach($this->recordToMessage->buildMessages($record) as $message) {
            $this->discord->send($message);
        }
    }

    /** @throws BindingResolutionException */
    protected function createRecordConverter(Container $container, Repository $config): RecordToMessage
    {
        $converter = $container->make(
            $config->get('discord-logger.converter', SimpleRecordConverter::class));

        if (!class_implements($converter, RecordToMessage::class)) {
            throw new RuntimeException('The converter specified in the discord-logger configuration should implement the RecordToMessage interface');
        }

        return $converter;
    }

}
