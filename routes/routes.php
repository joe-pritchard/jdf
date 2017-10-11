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

Route::get('/jmf/return-jmf', function () {
    foreach(Request::all() as $key => $value) {
        $string = $key . ': ' . $value . PHP_EOL;
    }

    Log::debug('got return jmf: ' . PHP_EOL);
})->name('joe-pritchard.return-jmf');