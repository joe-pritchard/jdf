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
];