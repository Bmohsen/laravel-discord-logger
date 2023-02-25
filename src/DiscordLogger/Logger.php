<?php

namespace Bmohsen\DiscordLogger;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Monolog\Logger as Monolog;

class Logger
{
    /** @var Repository */
    private $config;

    /** @var Container */
    private $container;

    public function __construct(Container $container, Repository $config)
    {
        $this->config = $config;
        $this->container = $container;
    }

    /** @throws BindingResolutionException */
    public function __invoke(array $config)
    {
        if (empty($config['url']))
        {
            throw new InvalidArgumentException('You must set the `url` key in your discord channel configuration');
        }

        return new Monolog($this->config->get('app.name'), [$this->newDiscordLogHandler($config)]);
    }

    /** @throws BindingResolutionException */
    protected function newDiscordLogHandler(array $config): LogHandler
    {
        return new LogHandler($this->container, $this->config, $config);
    }
}
