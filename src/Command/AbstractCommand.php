<?php


namespace Beanie\Command;


use Beanie\Exception\BadFormatException;
use Beanie\Exception\InternalErrorException;
use Beanie\Exception\InvalidNameException;
use Beanie\Exception\UnknownCommandException;
use Beanie\Exception\OutOfMemoryException;


use Beanie\Server\Server;

abstract class AbstractCommand implements Command
{
    /**
     * @inheritDoc
     * @throws BadFormatException
     * @throws InternalErrorException
     * @throws OutOfMemoryException
     * @throws UnknownCommandException
     */
    public function parseResponse($responseLine, Server $server)
    {
        switch ($responseLine) {
            case Response::ERROR_BAD_FORMAT:
                throw new BadFormatException($this, $server);
            case Response::ERROR_INTERNAL_ERROR:
                throw new InternalErrorException($this, $server);
            case Response::ERROR_OUT_OF_MEMORY:
                throw new OutOfMemoryException($this, $server);
            case Response::ERROR_UNKNOWN_COMMAND:
                throw new UnknownCommandException($this, $server);
            default:
                return $this->parseResponseLine($responseLine, $server);
        }
    }

    /**
     * @param string $responseLine
     * @param Server $server
     * @return Response
     */
    abstract protected function parseResponseLine($responseLine, Server $server);

    /**
     * @param string $name
     * @throws InvalidNameException
     */
    protected function ensureValidName($name)
    {
        if (!(
            is_string($name) &&
            strlen($name) <= 200 &&
            preg_match(Command::VALID_NAME_REGEX, $name)
        )) {
            if (is_object($name) && !method_exists($name, '__toString')) {
                $name = sprintf('{object of type %s', get_class($name));
            }

            throw new InvalidNameException("Invalid name: {$name}");
        }
    }

    /**
     * @inheritdoc
     */
    public function hasData()
    {
        return $this->getData() !== null;
    }

    /**
     * @inheritdoc
     */
    public function getData()
    {
        return null;
    }
}
