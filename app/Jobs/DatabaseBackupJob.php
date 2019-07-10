<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class DatabaseBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $commands = [];

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
    	$this->buildCommands();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
    	foreach ($this->commands as $command) {
		    try {
			    (new Process($command))->mustRun();
		    } catch (ProcessFailedException $exception) {
			    //TODO: do something with exception
	        }
        }
    }

    private function buildCommands() {
	    foreach(config('database-backup') as $database) {
	    	$path = storage_path("backups/" . date('Y-m-d') . '/');
	    	$file = $path . $database .'.backup.sql';
	    	if (! File::isDirectory($path)) {
	    		File::makeDirectory($path, 0777, true);
		    }

		    if (! File::exists($file)) {
			    $this->commands[] = sprintf(
				    'mysqldump -u%s -p%s %s > %s',
				    config('database.connections.mysql.username'),
				    config('database.connections.mysql.password'),
				    $database,
				    $file);
		    }
	    }
    }
}
