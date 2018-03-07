<?php
/**
 * ReturnJMFReceived.php
 *
 * @project  jdf.git
 * @category JoePritchard\JDF\Events
 * @author   Joe Pritchard <joe@joe-pritchard.uk>
 *
 * Created:  07/03/2018 10:25
 *
 */

namespace JoePritchard\JDF\Events;


/**
 * Class ReturnJMFReceived
 *
 * @package JoePritchard\JDF\Events
 */
class ReturnJMFReceived
{
    /**
     * @var array
     */
    public $parameters;

    /**
     * ReturnJMFReceived constructor.
     *
     * @param array $parameters
     */
    public function __construct(array $parameters)
    {
        $this->parameters = $parameters;
    }
}