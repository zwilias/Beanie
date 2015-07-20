<?php


namespace Beanie\Server;


use Beanie\Exception\SocketException;

class Socket
{
    /** @var bool */
    protected $_connected = false;

    /** @var string */
    protected $_hostname;

    /** @var int */
    protected $_port;

    /** @var resource */
    protected $_socket;

    /**
     * @param string $hostname
     * @param int $port
     * @throws SocketException When socket creation fails. Will have the underlying code and message.
     */
    public function __construct($hostname = Server::DEFAULT_HOST, $port = Server::DEFAULT_PORT)
    {
        $this->_hostname = (string)$hostname;
        $this->_port = (int)$port;

        if (($this->_socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
            $errorCode = socket_last_error();
            $errorMessage = socket_strerror($errorCode);
            throw new SocketException($errorMessage, $errorCode);
        }
    }

    public function write($data)
    {
        $this->_ensureConnected();
        $dataLength = $leftToWrite = strlen($data);

        do {
            if (
                ($written = socket_write(
                    $this->_socket,
                    substr($data, -$leftToWrite),
                    $leftToWrite
                )) === false
            ) {
                $errorCode = socket_last_error();
                $errorMessage = socket_strerror($errorCode);
                throw new SocketException($errorMessage, $errorCode);
            }

            $leftToWrite -= $written;
        } while ($leftToWrite > 0);

        return $dataLength;
    }

    /**
     * @return boolean
     */
    public function isConnected()
    {
        return $this->_connected;
    }

    /**
     * @return string
     */
    public function getHostname()
    {
        return $this->_hostname;
    }

    /**
     * @return int
     */
    public function getPort()
    {
        return $this->_port;
    }

    /**
     * @return resource
     */
    public function getRaw()
    {
        return $this->_socket;
    }

    public function connect()
    {
        if (($this->_connected = socket_connect($this->_socket, $this->_hostname, $this->_port)) === false) {
            $errorCode = socket_last_error();
            $errorMessage = socket_strerror($errorCode);
            throw new SocketException($errorMessage, $errorCode);
        }
    }

    protected function _ensureConnected()
    {
        if ($this->_connected !== true) {
            throw new SocketException('Socket is not connected.');
        }
    }
}
