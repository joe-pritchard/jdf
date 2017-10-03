<?php
declare(strict_types=1);
/**
 * JMFResponseException
 *
 * @category JDF
 * @author   Joe Pritchard
 *
 * Created:  27/09/2017 12:51
 *
 */

namespace JoePritchard\JDF\Exceptions;

/**
 * Class JMFResponseException
 *
 * @package JoePritchard\JDF\Exceptions
 */
class JMFResponseException extends \Exception
{

    /**
     * JMFResponseException constructor.
     */
    public function __construct($string)
    {
        parent::__construct($string);
    }
}