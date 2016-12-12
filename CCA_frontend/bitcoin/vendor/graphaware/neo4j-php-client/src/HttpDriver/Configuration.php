<?php

namespace GraphAware\Neo4j\Client\HttpDriver;

use GraphAware\Common\Driver\ConfigInterface;

class Configuration implements ConfigInterface
{
    /**
     * @var int
     */
    protected $timeout;

    /**
     * @var string
     */
    protected $curlInterface;

    /**
     * @return Configuration
     */
    public static function create()
    {
        return new self();
    }

    /**
     * @param int $timeout
     *
     * @return Configuration
     */
    public function withTimeout($timeout)
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * @param string $interface
     *
     * @return $this
     */
    public function withCurlInterface($interface)
    {
        $this->curlInterface = $interface;

        return $this;
    }

    /**
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @return string
     */
    public function getCurlInterface()
    {
        return $this->curlInterface;
    }
}
