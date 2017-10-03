<?php
declare(strict_types=1);
/**
 * JDFCore.php
 *
 * @category JDF
 * @author   Joe Pritchard
 *
 * Created:  25/09/2017 14:50
 *
 */

namespace JoePritchard\JDF;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use JoePritchard\JDF\Exceptions\JMFSubmissionException;
use JoePritchard\JDF\Exceptions\WorkflowNotFoundException;
use Storage;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;


/**
 * Class Manager
 *
 * @package JoePritchard\JDF
 */
class Manager
{
    /**
     * @var Collection
     */
    private $files_to_send;

    /**
     * @var Collection
     * eg. [['name' => 'something', 'url' => 'something'], ['name' => 'something', 'url' => 'something']]
     *
     */
    private $target_workflows;

    /**
     * @var Collection
     * eg. [['some workflow name' => 'some workflow url', 'other workflow name' => 'other workflow url']]
     */
    private $available_workflows;

    /**
     * Manager constructor.
     */
    public function __construct()
    {
        $this->files_to_send       = new Collection();
        $this->target_workflows    = new Collection();
        $this->available_workflows = new Collection();
    }

    /**
     * JDFCore destructor. Send any files that have been queued before we die
     */
    public function __destruct()
    {
        if ($this->files_to_send->count() && $this->target_workflows->count()) {
            $this->target_workflows->each(function($workflow) {
                foreach ($this->files_to_send as $file) {
                    $this->submitQueueEntry($file, $workflow);
                }
            });
        }
    }

    /**
     * Queue up this file to be sent to freeflow core (sends on destruct).
     *
     * @param string $filename  This should be the path to a JDF file relative to the JMF server, OR an absolute URL to a JDF file
     *
     * @return $this
     */
    public function sendFile(string $filename): Manager
    {
        if (!Str::endsWith($filename, '.jdf')) {
            throw new \InvalidArgumentException('Please only send JDF files to the JMF server');
        }

        if (!filter_var($filename, FILTER_VALIDATE_URL)) {
            // If we're not sending a URL to the JMF server, then we want to make sure we're sending a file resource to it
            $filename = Str::start($filename, 'file:///');
        }

        $this->files_to_send->push($filename);

        return $this;
    }

    /**
     * Send multiple files at once (calls sendFile)
     *
     * @param array $files
     *
     * @return Manager
     */
    public function sendFiles(array $files): Manager
    {
        foreach ($files as $filename) {
            $this->sendFile($filename);
        }

        return $this;
    }

    /**
     * Store the target workflow to send files to.
     * Multiple workflows can be selected, just call this method again!
     *
     * @param string $workflow
     *
     * @return $this
     * @throws WorkflowNotFoundException
     */
    public function toDestination(string $workflow): Manager
    {
        if (!$this->workflowExists($workflow)) {
            throw new WorkflowNotFoundException('The workflow ' . $workflow . ' was not found on the Freeflow Core Server');
        }

        $this->target_workflows->push(collect(['name' => $workflow, 'url' => $this->available_workflows->get($workflow)]));

        return $this;
    }

    /**
     * Ask Freeflow Core if it has a workflow with this name
     *
     * @param string $workflow_name
     * @return bool
     */
    private function workflowExists(string $workflow_name): bool
    {
        if ($this->available_workflows->where('name', $workflow_name)->count() > 0) {
            // we've already been told about this workflow, so answer from memory
            echo $workflow_name . 'exists, I asked already' . PHP_EOL;
            return true;
        }

        $jmf = new JMF;

        $jmf->query()->addAttribute('Type', 'KnownControllers');
        $jmf->query()->addAttribute('ID', Hash::make(time()));

        $response = $jmf->submitMessage();

        foreach ($response->JDFController as $controller) {
            $controller_id  = (string) $controller->attributes()->ControllerID;
            $controller_url = (string) $controller->attributes()->URL;

            // remember this in case we can save time later by getting it straight out of the collection
            if ($this->available_workflows->where('name', $workflow_name)->count() === 0) {
                $this->available_workflows->push(collect(['name' => $controller_id, 'url' => $controller_url]));
            }

            if ($controller_id === $workflow_name) {
                // This controller matches!
                return true;
            }
        }

        return false;
    }

    /**
     * function submitQueueEntry
     *
     * @param string     $file_url  This is the url of the JDF file to send to Core
     * @param Collection $workflow  This is the workflow object, whose URL will be used in our submission
     *
     * @throws JMFSubmissionException
     */
    private function submitQueueEntry(string $file_url, Collection $workflow): void
    {

        $jmf = new JMF;

        $jmf->command()->addAttribute('Type', 'SubmitQueueEntry');
        $jmf->command()->addAttribute('xsi:type', 'CommandSubmitQueueEntry', 'xsi');
        $jmf->command()->addAttribute('ID', Str::random());
        $jmf->command()->addChild('QueueSubmissionParams')->addAttribute('URL', $file_url);

        $response = $jmf->setDevice($workflow->get('name'))->submitMessage();

        if ((int)$response->attributes()->ReturnCode > 0) {
            dump($response->asXML());
            throw new JMFSubmissionException((string)$response->Notification->Comment);
        }
    }
}