<?php
declare(strict_types=1);
/**
 * routes.php
 *
 * @project  Artery
 * @author   joe
 *
 * Created:  11/10/2017 16:02
 *
 */

use Illuminate\Http\Request;

if (config('jdf.enable_return_jmf') && !config('jdf.return_jmf_url', false)) {
    Route::post('/jmf/return-jmf', function (Request $request) {
        event(new \JoePritchard\JDF\Events\ReturnJMFReceived($request->all()));
    })->name('joe-pritchard.return-jmf');
}