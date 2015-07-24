<?php


namespace Beanie\Command\ResponseParser;


use Beanie\Server\Server;

interface ResponseParser
{
    /**
     * @param string $responseLine
     * @param Server $server
     * @return \Beanie\Command\Response
     */
    public function parseResponse($responseLine, Server $server);
}
