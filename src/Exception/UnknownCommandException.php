<?php


namespace Beanie\Exception;


class UnknownCommandException extends AbstractServerCommandException
{
    const DEFAULT_CODE = 405;
    const DEFAULT_MESSAGE = 'Tried to execute unknown method \'%s\' on \'%s\'';
}
