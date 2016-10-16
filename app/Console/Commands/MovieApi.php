<?php

namespace App\Console\Commands;

use App\Models\Fail;
use App\Models\Movie;
use App\Repositories\ApiCrawler;
use GuzzleHttp\Client;
use Illuminate\Console\Command;

class MovieApi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'movie:api';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Read failed csv data, and get it from api omdb.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Fail::chunk(100, function ($movies) {
            foreach ($movies as $movie) {

                $this->info("Processing {$movie->imdb_id}");

                $client = new Client();
                $resShort = $client->request('GET', 'http://www.omdbapi.com/?i=' . $movie->imdb_id);
                $resFull = $client->request('GET', 'http://www.omdbapi.com/?i=' . $movie->imdb_id . '&plot=full&tomatoes=true');
                $bodyShort = $resShort->getBody();
                $bodyFull = $resFull->getBody();
                $bodyShort = json_decode($bodyShort);
                $bodyFull = json_decode($bodyFull);


                if ($bodyShort->Response == "True") {

                    $api = new ApiCrawler($this);

                    if (Movie::where('imdb_id', $bodyShort->imdbID)->first()) {
                        $this->info("Skip {$movie->imdb_id}\n");

                        continue;
                    }

                    $api->saveToDatabase($bodyShort, $bodyFull);

                } elseif ($bodyShort->Response == "False") {
                    $this->error("Not found {$movie->imdb_id}");
                    $this->info("");

                }

            }
        });

    }

}