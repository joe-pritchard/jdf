<?php
declare(strict_types=1);
/**
 * JMFReturnCodeException
 *
 * @project  Artery
 * @category JoePritchard\JDF
 * @author   joe
 *
 * Created:  11/10/2017 10:08
 *
 */

namespace JoePritchard\JDF\Exceptions;


/**
 * Class JMFReturnCodeException
 *
 * @package JoePritchard\JDF
 */
class JMFReturnCodeException extends \Exception
{

    /**
     * JMFReturnCodeException constructor.
     *
     * @param string $param
     */
    public function __construct($string)
    {
        parent::__construct($string);
    }
}