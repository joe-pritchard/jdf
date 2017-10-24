<?php
declare(strict_types=1);
/**
 * JMFEntrySubmittedhp
 *
 * @project  jdf.git
 * @category JoePritchard\JDF\Events
 * @author   joe
 *
 * Created:  12/10/2017 09:54
 *
 */

namespace JoePritchard\JDF\Events;

/**
 * Class JMFEntrySubmitted. Fires when a new JDF file is submitted to the JMF server
 *
 * @package JoePritchard\JDF\Events
 */
class JMFEntrySubmitted
{
    /**
     * @var \SimpleXMLElement
     */
    public $jmf_request;

    /**
     * @var \SimpleXMLElement
     */
    public $jmf_response;

    /**
     * JMFEntrySubmitted constructor.
     *
     * @param \SimpleXMLElement $jmf_request
     * @param \SimpleXMLElement $jmf_response
     */
    public function __construct(\SimpleXMLElement $jmf_request, \SimpleXMLElement $jmf_response)
    {
        $this->jmf_response = $jmf_response;
        $this->jmf_request  = $jmf_request;
    }
}