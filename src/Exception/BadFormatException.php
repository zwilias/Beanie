<?php


namespace Beanie\Exception;


use Beanie\Command;
use Beanie\Exception;


class BadFormatException extends AbstractServerCommandException
{
    const DEFAULT_CODE = 400;
    const DEFAULT_MESSAGE = 'Failed executing \'%s\' command on \'%s\': BAD_FORMAT';
}
