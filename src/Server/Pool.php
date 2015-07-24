<?php


namespace Beanie\Server;


use Beanie\Command\AbstractCommand;
use Beanie\Exception\InvalidArgumentException;
use Beanie\Tube\TubeAware;
use Beanie\Tube\TubeStatus;

class Pool implements TubeAware
{
    use TubeAwareTrait;

    /** @var Server[] */
    protected $servers = [];

    /**
     * @param Server[] $servers
     * @throws InvalidArgumentException
     */
    public function __construct(array $servers)
    {
        if (!count($servers)) {
            throw new InvalidArgumentException('Pool needs servers');
        }

        foreach ($servers as $server) {
            $this->addServer($server);
        }

        $this->tubeStatus = new TubeStatus();
    }

    /**
     * @param Server $server
     * @return $this
     */
    protected function addServer(Server $server)
    {
        $this->servers[(string) $server] = $server;
        return $this;
    }

    /**
     * @return Server[]
     */
    public function getServers()
    {
        return $this->servers;
    }

    /**
     * @param string $name
     * @return Server
     * @throws InvalidArgumentException
     */
    public function getServer($name)
    {
        if (!isset($this->servers[$name])) {
            throw new InvalidArgumentException('Server not found by name: ' . $name);
        }

        return $this->servers[$name];
    }

    /**
     * @param AbstractCommand $command
     * @return \Beanie\Command\Response
     */
    public function dispatchCommand(AbstractCommand $command)
    {
        return $this
            ->getRandomServer()
            ->transformTubeStatusTo($this->tubeStatus)
            ->dispatchCommand($command);
    }

    /**
     * @return Server
     */
    protected function getRandomServer()
    {
        return $this->servers[array_rand($this->servers, 1)];
    }
}
