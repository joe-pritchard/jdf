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

use Event;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use JoePritchard\JDF\Events\JMFEntryFailed;
use JoePritchard\JDF\Events\JMFEntrySubmitted;
use JoePritchard\JDF\Exceptions\JMFResponseException;
use JoePritchard\JDF\Exceptions\JMFReturnCodeException;
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
    public function sendJDFFile(string $filename): Manager
    {
        if (!Str::endsWith($filename, '.jdf')) {
            throw new \InvalidArgumentException('Please only send JDF files to the JMF server');
        }

        $this->files_to_send->push($filename);

        return $this;
    }

    /**
     * Send multiple files at once (calls sendJDFFile)
     *
     * @param array $files
     *
     * @return Manager
     */
    public function sendJDFFiles(array $files): Manager
    {
        foreach ($files as $filename) {
            $this->sendJDFFile($filename);
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
            return true;
        }

        if (config('jdf.cache_controllers', false)) {
            $workflows = Cache::remember('jdf.workflows', 120, function () {
                return self::getWorkflows();
            });
        } else {
            try {
                $workflows = self::getWorkflows();
            } catch (JMFResponseException $e) {
                return false;
            } catch (JMFReturnCodeException $e) {
                return false;
            } catch (JMFSubmissionException $e) {
                return false;
            }
        }

        return $workflows->where('name', $workflow_name)->count() > 0;
    }

    /**
     * function submitQueueEntry
     *
     * @param string     $file_url  This is the url of the JDF file to send to Core
     * @param Collection $workflow  This is the workflow object, whose URL will be used in our submission
     *
     */
    private function submitQueueEntry(string $file_url, Collection $workflow): void
    {

        $jmf = new JMF;

        $jmf->command()->addAttribute('Type', 'SubmitQueueEntry');
        $jmf->command()->addAttribute('xsi:type', 'CommandSubmitQueueEntry', 'xsi');
        $queue_submission_params = $jmf->command()->addChild('QueueSubmissionParams');
        $queue_submission_params->addAttribute('URL', $jmf->formatPrintFilePath($file_url));
        $queue_submission_params->addAttribute('ReturnJMF', config('jdf.return_jmf_url') ?? route('joe-pritchard.return-jmf'));

        $message  = $jmf->getMessage();

        try {
            $response = $jmf->setDevice($workflow->get('name'))->submitMessage();
        } catch (JMFReturnCodeException $exception) {
            Event::dispatch(new JMFEntryFailed($message, $exception->getMessage()));
            return;
        } catch (JMFSubmissionException $exception) {
            Event::dispatch(new JMFEntryFailed($message, $exception->getMessage()));
            return;
        } catch (JMFResponseException $exception) {
            Event::dispatch(new JMFEntryFailed($message, $exception->getMessage()));
            return;
        }

        // dispatch an event so the application can pick up on this response
        Event::dispatch(new JMFEntrySubmitted($message, $response));
    }

    /**
     * Get all workflows from the controller
     * @static function getWorkflows
     * @return Collection
     * @throws JMFResponseException
     * @throws JMFReturnCodeException
     * @throws JMFSubmissionException
     */
    private static function getWorkflows(): Collection
    {
        return cache()->remember('joe-pritchard.jdf.workflows', 60, function() {
            $workflows = new Collection;

            $jmf = new JMF;
            $jmf->query()->addAttribute('Type', 'KnownControllers');

            $response = $jmf->submitMessage()->Response;

            foreach ($response->JDFController as $controller) {
                $controller_id = (string)$controller->attributes()->ControllerID;
                $controller_url = (string)$controller->attributes()->URL;
                if ($workflows->where('name', $controller_id)->count() === 0) {
                    $workflows->push(collect(['name' => $controller_id, 'url' => $controller_url]));
                }
            }

            return $workflows;
        });
    }

    /**
     * Get jobs from the JMF server.
     *
     * @param array $filters array of filter attributes, as follows:
     * @internal param string $device Specify the device to get only jobs on a certain workflow/controller
     * @internal param string $status Specify to get only jobs in a certain status. Must be 'Completed', 'InProgress', 'Suspended', or 'Aborted'
     * @internal param int $job_id
     *
     * @return Collection
     */
    public function getJobs(array $filters = []): \Illuminate\Support\Collection
    {
        $jmf  = new JMF();
        $jobs = new Collection;

        // todo: actually use the device filter
        $device = isset($filters['device']) ? $filters['device'] : '';
        $status = isset($filters['status']) ? $filters['status'] : '';
        $job_id = isset($filters['job_id']) ? (int)$filters['job_id'] : 0;

        $jmf->query()->addAttribute('Type', 'QueueStatus');
        $jmf->query()->addAttribute('xsi:type', 'QueryQueueStatus', 'xsi');

        $queue_filter = $jmf->query()->addChild('QueueFilter');
        $queue_filter->addAttribute('QueueEntryDetails', 'Brief');

        if ($status !== '' && !in_array($status, ['Completed', 'InProgress', 'Suspended', 'Aborted'])) {
            // silently ignore invalid statuses
            return $jobs;
        }
        if ($status !== '') {
            // StatusList only supports one status (despite its name)
            $queue_filter->addAttribute('StatusList', $status);
        }
        if ($job_id > 0) {
            $queue_entry_def = $queue_filter->addChild('QueueEntryDef');
            $queue_entry_def->addAttribute('QueueEntryID', (string)$job_id);
        }
        if ($device !== '') {
            $jmf->setDevice($device);
        }

        $response = $jmf->submitMessage()->Response;

        foreach ($response->Queue as $queue) {
            foreach ($queue->QueueEntry as $queue_entry) {
                $jobs->push([
                    'DeviceID'       => urldecode((string)$queue->attributes()->DeviceID),
                    'QueueEntryID'   => (string)$queue_entry->attributes()->QueueEntryID,
                    'Status'         => (string)$queue_entry->attributes()->Status,
                    'SubmissionTime' => (string)$queue_entry->attributes()->SubmissionTime,
                    'StartTime'      => (string)$queue_entry->attributes()->StartTime,
                    'EndTime'        => (string)$queue_entry->attributes()->EndTime,
                ]);
            }
        }

        return $jobs;
    }

    /**
     * Use getJobs to get one specific job
     * Returns a collection of arrays because JMF controller may return more than one entry for a certain job ID
     * (for example if you grouped multiple files into one submission)
     *
     * @param int $job_id
     *
     * @return Collection
     */
    public function getJobStatus(int $job_id)
    {
        return $this->getJobs(['job_id' => $job_id]);
    }
}