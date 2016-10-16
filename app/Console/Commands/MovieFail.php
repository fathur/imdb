<?php

namespace App\Console\Commands;

use App\Models\Fail;
use App\Models\Movie as Film;
use Illuminate\Console\Command;

class MovieFail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'movie:fail
        {--O|offset= : start from id offset}
        {--K|take= : how many data that you want take}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Collect failed insert movie database';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $offset = $this->option('offset');
        $take = $this->option('take');

        for ($i = $offset; $i <= $take; $i ++) {
            $this->info("Processing {$i}");
            $film = Film::where('imdb_id', $this->formatId($i))->count();
            if ($film == 0) {

                $fail = Fail::where('imdb_id', $this->formatId($i))->count();

                if($fail == 0) {
                    Fail::create([
                        'imdb_id' => $this->formatId($i)
                    ]);
                }
            }
        }

    }

    /**
     * @param $number
     *
     * @return string
     */
    private function formatId($number)
    {
        $digitCount = strlen((string)$number);

        $zeroCount = 7 - $digitCount;

        $string = "tt";

        for ($i = 0; $i < $zeroCount; $i ++) {
            $string .= "0";
        }

        $string .= $number;

        return $string;
    }
}