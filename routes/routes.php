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

if (config('jdf.enable-return-jmf') && !config('jdf.return-jmf-url')) {
    Route::get('/jmf/return-jmf', function () {
        // todo: return jmf route
    })->name('joe-pritchard.return-jmf');
}