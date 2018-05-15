# JDF #

A Simple package for creating JDF files and sending them via JMF.

[JDF](https://en.wikipedia.org/wiki/Job_Definition_Format) is an XML standard used to send files to digital printers and
communicate workflow steps to printers and finishers. JMF is a communications protocol used to query "JMF Managers" 
(RIP computers and print servers etc) for job/queue status, and to submit JDF files to them for processing

This package does not implement the entirety of either of these standards, it simply enables you to create JDF files
for the printing of an arbitrary number of files, and then send those files to a JMF manager and query the Manager for 
the status of its jobs.

## Installation ##

`composer require joe-pritchard/jdf`

**Configuration**

To override the default behaviour, publish and modify the config file:

`php artisan vendor:publish --provider=JoePritchard\\JDF\\Providers\\ServiceProvider`   

The following config options can be changed:

 - server_url: The URL at which we can communicate with your JMF controller
 - server_file_path: If you submit relative file paths, we'll prepend them with this path. Use when submitting instructions to print files which are local to the JMF controller 
 - sender_id: This is like your user agent
 - submit_queue_entry_callback: A listener to call when jobs are successfuly submitted to the controller. MUST implement a handle() method
 - failed_queue_entry_callback: Same thing but when a submission fails
 - enable_return_jmf: Whether or not to request the controller makes a rerturn jmf call to your app
 - return_jmf_url: Where should the controller send its return jmf message
 
More descriptions are available for each option in the config file itself.

**Events**

We will emit the following events:

 - `JoePritchard\JDF\Events\JMFEntryFailed`: When we couldn't submit a job to the controller
 - `JoePritchard\JDF\Events\JMFEntrySubmitted`: When a job is submitted to the controller successfully
 - `JoePritchard\JDF\Events\ReturnJMFReceived`: When a post is received on our return JMF route. If you enable return jmf but don't specify a URL, then we'll listen on /jmf/return-jmf and fire this event when we get a hit from the controller
 
If you'd rather not provide callback listeners in the config file, you can just listen to these events in your application. 

## Usage ##

**Create a new JDF file**

```
// instantiate a JDF object
$jdf = new \JoePritchard\JDF\JDF();
// add a new print file to the JDF
$jdf->addPrintFile('http://absolute/path/to/file.pdf', 1, $item_id);
// add a print file which is accessible locally to the JMF controller
$jdf->addPrintFile('relative/path/to/file.pdf', 1, $item_id);
// save the raw JDF to a file
file_put_contents('filename.jdf', $jdf->getRawMessage());
```

**Send JDF to a controller**

To the default controller (this will only work if you have specified server_url in your config file):

```
$jdf_manager = new \JoePritchard\JDF\Manager;
$jdf_manager->sendJDFFile('http://absolute/path/to/filename.jdf')->toDestination('workflow name');
```

To a specific controller (either to override the default or if you haven't specified a URL in your config file):

```
//This can't currently be done :(
```

**Get job status from a controller**

```
$jmf_manager = new \JoePritchard\JDF\Manager;

// get all jobs from the controller
$jobs = $jmf_manager->getJobs();

// get all jobs from the controller on a specific workflow/queue
$jobs = $jmf_manager->getJobs(['device' => 'Workflow Name']);

// get all jobs from the controller with a certain status
$jobs = $jmf_manager->getJobs(['status' => 'Completed/InProgress/Suspended/Aborted']);

// get a specific job from the controller
$job = $jmf_manager->getJobs(['job_id' => '9999']);

```

getJobs returns a collection of arrays in the format:

```
[
    'DeviceID'       => 'Workflow / Queue Name',
    'QueueEntryID'   => 'Job ID on controller',
    'Status'         => 'Job status on the controller',
    'SubmissionTime' => 2018-05-14 00:00:00,
    'StartTime'      => 2018-05-14 00:00:00,
    'EndTime'        => 2018-05-14 00:00:00,
]
```
 