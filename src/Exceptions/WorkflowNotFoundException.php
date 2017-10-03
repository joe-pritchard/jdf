<?php
declare(strict_types=1);
/**
 * WorkflowNotFoundException
 *
 * @category JDF\Exception
 * @author   Joe Pritchard
 *
 * Created:  26/09/2017 14:51
 *
 */

namespace JoePritchard\JDF\Exceptions;


/**
 * Class WorkflowNotFoundException
 *
 * @package JoePritchard\JDF\Exceptions
 */
class WorkflowNotFoundException extends \Exception
{

    /**
     * WorkflowNotFoundException constructor.
     *
     * @param string $string
     */
    public function __construct(string $string)
    {
        parent::__construct($string);
    }
}