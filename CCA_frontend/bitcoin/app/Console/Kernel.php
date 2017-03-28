<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

/**
 * Implementacia planovaca pre automaticke ulohy.
 *
 * Pozor:
 * Pre zabezpecenie funkcnosti planovania je potreba nastavit nasledujuci prikaz na strane serveru aby sa vykonaval kazdu minutu.
 * php /path/to/artisan schedule:run >> /dev/null 2>&1 (artisan je umiestneny v koreni projektu)
 *

 *
 * Class Kernel
 * @package App\Console
 *
 * @author Tomas Drozda <tomas.drozda@icloud.com>
 */
class Kernel extends ConsoleKernel
{
    /**
     * Artisan prikazy.
     *
     * @var array
     */
    protected $commands = [
//         Commands\DownloadTags::class,
//         Commands\DownloadBlocks::class,
//         Commands\Inspire::class,
         Commands\ParseBlocks::class,
         Commands\AddTag::class
    ];

    /**
     * Difinicia planovania spustania uloh.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        /**
         * Stahovanie tagov.
         */
        $schedule->command('tags:download')->withoutOverlapping()
            ->everyThirtyMinutes();
        /**
         * Stahovanie obsahu blockchainu.
         */
        $schedule->command('blocks:download')->withoutOverlapping()
            ->hourly();
    }
}
