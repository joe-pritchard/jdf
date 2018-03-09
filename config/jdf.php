<?php
declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | JMF Server URL
    |--------------------------------------------------------------------------
    |
    | This is the internet address of the jmf host server to which all of our
    | jmf messages will by submitted by default. You do have the option to
    | override this by sending $url in the JMF::submitMessage() method
    */

    'server_url' => '',

    /*
    |--------------------------------------------------------------------------
    | JMF Server File Path
    |--------------------------------------------------------------------------
    |
    | This is prepended to files referenced in our jdf files or our JMF message
    | This value is used in the addPrintFile() and SubmitQueueEntry, with the
    | assumption that the JMF server will find the files somewhere local
    */

    'server_file_path' => '',

    /*
    |--------------------------------------------------------------------------
    | Sender ID
    |--------------------------------------------------------------------------
    |
    | This is the name of your application. Use it to identify yourself to the
    | JMF server. If you do not set a value in this config key, then we will
    | use your applications name (the one in your config's app.name key)
    */

    'sender_id' => '',

    /*
    |--------------------------------------------------------------------------
    | Submit Queue Entry Callback
    |--------------------------------------------------------------------------
    |
    | The fully qualified name of a listener you would like me to call whenever
    | a JMF message is submitted successfully. Alternatively you could simply
    | listen for the JoePritchard\JDF\JMFEntrySubmitted event in your app
    */

    'submit_queue_entry_callback' => '',

    /*
    |--------------------------------------------------------------------------
    | Failed Queue Entry Callback
    |--------------------------------------------------------------------------
    |
    | The fully qualified name of a listener you would like me to call whenever
    | a JMF message fails to be submitted. Alternatively you could simply
    | listen for the JoePritchard\JDF\JMFEntryFailed event in your app
    */

    'failed_queue_entry_callback' => '',

    /*
    |--------------------------------------------------------------------------
    | Enable Return JMF
    |--------------------------------------------------------------------------
    |
    | Do you want to enable ReturnJMF? If enabled, we will ask the JMF server
    | to send us status updates for all jobs we submit via SubmitQueueEntry
    | Either way you will continue to receive job status via the callback
    |
    */

    'enable_return_jmf' => false,

    /*
    |--------------------------------------------------------------------------
    | Return JMF URL
    |--------------------------------------------------------------------------
    |
    | The URL we will ask the JMF server to send status updates to. If ReturnJMF
    | is enabled, we will prefer this URL and fall back to our internal route,
    | which is accessible at /jmf/return-jmf
    |
    */

    'return_jmf_url' => null,
];