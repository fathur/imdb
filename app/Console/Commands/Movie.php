<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Movie extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'movie
        {--F|file= : split file}
        {--O|offset= : start from id offset}
        {--K|take= : how many data that you want take}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inserting movie into database';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $offset = $this->option('offset');
        $take = $this->option('take');

        $inputFile = $this->option('file');

        $dumpLoader = new \App\Repositories\DumpLoader(base_path($inputFile), $offset, $take);
        $dumpLoader->saveToDatabase($this);

    }
}