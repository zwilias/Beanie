<?php


namespace Beanie;


use Beanie\Command\Command;
use Beanie\Command\CommandFactory;
use Beanie\Job\JobFactory;
use Beanie\Server\Server;
use Beanie\Tube\Tube;

class Manager
{
    /** @var Server */
    protected $server;

    /** @var CommandFactory */
    protected $commandFactory;

    /** @var JobFactory */
    protected $jobFactory;

    /**
     * @param Server $server
     */
    public function __construct(Server $server)
    {
        $this->server = $server;
        $this->commandFactory = CommandFactory::instance();
        $this->jobFactory = JobFactory::instance();
    }

    /**
     * @return array
     * @throws Exception\InvalidArgumentException
     */
    public function stats()
    {
        return $this->server->dispatchCommand($this->commandFactory->create(Command::COMMAND_STATS))->getData();
    }

    /**
     * @param int $jobId
     * @return Job\Job|null
     * @throws Exception\InvalidArgumentException
     */
    public function peek($jobId)
    {
        return $this->jobFactory->createFromCommand(
            $this->commandFactory->create(Command::COMMAND_PEEK, [$jobId]),
            $this->server
        );
    }

    /**
     * @return Tube[]
     * @throws Exception\InvalidArgumentException
     */
    public function tubes()
    {
        $tubes = [];

        foreach (
            $this->server->dispatchCommand(
                $this->commandFactory->create(Command::COMMAND_LIST_TUBES)
            )->getData() as $tubeName
        ) {
            $tubes[] = new Tube($tubeName, $this->server);
        }

        return $tubes;
    }
}
