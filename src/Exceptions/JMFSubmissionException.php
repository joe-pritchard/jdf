<?php
declare(strict_types=1);
/**
 * JMFSubmissionException
 *
 * @category JoePritchard\JDF\Exceptions
 * @author   Joe Pritchard
 *
 * Created:  26/09/2017 15:18
 *
 */

namespace JoePritchard\JDF\Exceptions;


/**
 * Class JMFSubmissionException
 *
 * @package JoePritchard\JDF\Exceptions
 */
class JMFSubmissionException extends \Exception
{

    /**
     * JMFSubmissionException constructor.
     *
     * @param string $string
     */
    public function __construct(string $string)
    {
        parent::__construct($string);
    }
}