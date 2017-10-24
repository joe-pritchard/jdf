<?php
declare(strict_types=1);
/**
 * JMF.php
 *
 * @category JDF
 * @author   Joe Pritchard
 *
 * Created:  02/10/2017 10:13
 *
 */

namespace JoePritchard\JDF;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use JoePritchard\JDF\Exceptions\JMFResponseException;
use JoePritchard\JDF\Exceptions\JMFReturnCodeException;
use JoePritchard\JDF\Exceptions\JMFSubmissionException;
use SimpleXMLElement;

/**
 * Class JMF
 *
 * @package JoePritchard\JDF
 */
class JMF extends BaseJDF
{
    /**
     * JMF constructor.
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->initialiseMessage('JMF');
    }

    /**
     * Add the DeviceID attribute to the root JMF element
     *
     * @param string $device
     *
     * @return JMF
     */
    public function setDevice(string $device): JMF
    {
        $this->root->addAttribute('DeviceID', $device);

        return $this;
    }

    /**
     * Submit the current JMF message to the JMF server using cURL. Re-init the message afterwards and return the response
     *
     * @param string|null $url If no URL is specified we will submit the message to the base JMF server url
     *
     * @return SimpleXMLElement
     * @throws JMFSubmissionException
     * @throws JMFResponseException
     * @throws JMFReturnCodeException
     */
    public function submitMessage($url = null, $initialise = false): SimpleXMLElement
    {
        // Make sure cURL is enabled
        if (!function_exists('curl_version')) {
            throw new \RuntimeException("cURL must be installed to send JMF messages.");
        }

        // use the url provided to us, or alternatively the base JMF server url
        $target = $url ?? $this->server_url;

        // add a timestamp to this message
        $this->root->addAttribute('TimeStamp', \Carbon\Carbon::now()->toIso8601String());

        // add the ID attribute to the command or query
        $message_id = Hash::make(time());
        if ($this->root->Command->asXML() !== false) {
            $this->command()->addAttribute('ID', $message_id);
        }
        if ($this->root->Query->asXML() !== false) {
            $this->query()->addAttribute('ID', $message_id);
        }

        $payload = $this->root->asXML();

        // ready to send the message... if it is a SubmitQueueEntry there will be a QueueSubmissionParams element with a URL attribute
        if ($this->root->Command->asXML() !== False && (string)$this->root->Command->attributes()->Type === 'SubmitQueueEntry') {
            $file_url = $this->root->Command->QueueSubmissionParams->attributes()->URL;

            // if the URL attribute starts with cid://, then we're going to have to construct a MIME Package
            if (Str::startsWith($file_url, 'cid://')) {
                $payload = $this->makeMimePackage();
            }
        }

        $curl_handle = curl_init($target);
        curl_setopt($curl_handle, CURLOPT_POST, 1);
        curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_handle, CURLOPT_HTTPHEADER, [
            'Content-type: application/vnd.cip4-jmf+xml',
        ]);

        $raw_result = curl_exec($curl_handle);
        curl_close($curl_handle);

        Log::debug($payload);

        if ($initialise) {
            $this->initialiseMessage('JMF');
        }

        if ($raw_result === false) {
            throw new JMFSubmissionException('Failed to communicate with the JMF server');
        }
        try {
            $result = new SimpleXMLElement($raw_result);
        } catch (\Exception $exception) {
            throw new JMFResponseException('The JMF server responded with Invalid XML: ' . $raw_result);
        }
        if ((int)$result->Response->attributes()->ReturnCode > 0) {
            Log::debug($result->asXML());
            throw new JMFReturnCodeException((string)$result->Response->Notification->Comment);
        }

        Log::debug($result->Response->asXML());

        return $result->Response;
    }

    /**
     * function makeMimePackage
     *
     * @return string
     */
    private function makeMimePackage(): string
    {
        // the boundary separates the parts of our message
        $boundary = Str::random(26);

        $mime_package = '';

        // we're going to try to get the contents of the JDF file, and then we're going to update the URL param to match ID we're gonna use
        $jdf_cid = '1.JDF';
        $jdf_data = base64_encode(file_get_contents(Str::after($this->root->Command->QueueSubmissionParams->attributes()->URL, 'cid://')));
        $this->root->Command->QueueSubmissionParams->attributes()->URL = 'cid://' . $jdf_cid;

        // first the MIME package header
        $mime_package .= 'MIME-Version: 1.0' . PHP_EOL;
        $mime_package .= 'Content-Description: JoePritchard JDF MIME Package' . PHP_EOL;
        $mime_package .= 'Content-Type: multipart/related; boundary="'.$boundary.'"' . PHP_EOL . PHP_EOL;

        // now the JMF
        $mime_package .= '--' . $boundary . PHP_EOL . PHP_EOL;
        $mime_package .= 'Content-ID: 1.JMF' . PHP_EOL;
        $mime_package .= 'Content-Type: application/vnd.cip4-jmf+xml' . PHP_EOL;
        $mime_package .= 'Content-Transfer-Encoding: base64' . PHP_EOL;
        $mime_package .= chunk_split(base64_encode($this->root->asXML()), 76, PHP_EOL) . PHP_EOL;

        // now the JDF
        $mime_package .= '--' . $boundary . PHP_EOL . PHP_EOL;
        $mime_package .= 'Content-ID: ' . $jdf_cid . PHP_EOL;
        $mime_package .= 'Content-Type: application/vnd.cip4-jdf+xml' . PHP_EOL;
        $mime_package .= 'Content-Transfer-Encoding: base64' . PHP_EOL;
        $mime_package .= chunk_split($jdf_data, 76, PHP_EOL) . PHP_EOL . '--' . $boundary . PHP_EOL;

        echo $mime_package;
        return $mime_package;
    }
}