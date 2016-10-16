<?php

namespace App\Repositories;

use App\Console\Commands\MovieApi;
use App\Models\Movie;
use App\Repositories\MovieConverter as M;
use Illuminate\Database\Eloquent\MassAssignmentException;
use InvalidArgumentException;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class ApiCrawler
{
    private $console;

    public function __construct($console = null)
    {
        $this->console = $console;

        $this->log = new Logger(__METHOD__);
        $this->log->pushHandler(new StreamHandler(storage_path() . '/logs/movie-api-failed-' . date('Y-m-d') . '.log'));

    }

    public function saveToDatabase($dataShort, $dataFull)
    {
        try {

            $movie = new Movie();
            $movie->imdb_id = isset($dataShort->imdbID) ? $dataShort->imdbID : null;
            $movie->title = isset($dataShort->Title) ? utf8_encode($dataShort->Title) : null;
            $movie->year = isset($dataShort->Year) ? (int)$dataShort->Year : null;
            $movie->rated = isset($dataShort->Rated) ? M::changeEmptyStringToNull($dataShort->Rated) : null;
            $movie->released = isset($dataShort->Released) ? M::toTimestamp($dataShort->Released) : null;
            $movie->runtime = isset($dataShort->Runtime) ? M::toMiliSeconds($dataShort->Runtime) : null;
            $movie->genres = isset($dataShort->Genre) ? M::explodeGenres($dataShort->Genre) : null;
            $movie->directors = isset($dataShort->Director) ? M::explodeDirectors($dataShort->Director) : null;
            $movie->writers = isset($dataShort->Writer) ? M::explodeWriters($dataShort->Writer) : null;
            $movie->actors = isset($dataShort->Actors) ? M::explodeActors($dataShort->Actors) : null;
            $movie->plot = [
                'short' => (isset($dataShort->Plot) AND $dataShort->Plot != "N/A") ? utf8_encode($dataShort->Plot) : null,
                'full'  => (isset($dataFull->Plot) AND $dataFull->Plot != "N/A") ? utf8_encode($dataFull->Plot) : null
            ];

            $movie->awards = (isset($dataShort->Awards) AND $dataShort->Awards != "N/A") ? M::changeEmptyStringToNull(utf8_encode($dataShort->Awards)) : null;
            $movie->poster = isset($dataShort->Poster) ? M::changeEmptyStringToNull($dataShort->Poster) : null;
            $movie->metascore = (isset($dataShort->Metascore) AND $dataShort->Metascore != "N/A") ? M::changeEmptyStringToNull($dataShort->Metascore) : null;
            $movie->imdb_rating = isset($dataShort->imdbRating) ? floatval($dataShort->imdbRating) : null;
            $movie->imdb_votes = isset($dataShort->imdbVotes) ? (int)$dataShort->imdbVotes : null;
            $movie->type = isset($dataShort->Type) ? utf8_encode($dataShort->Type) : null;
            $movie->languages = (isset($dataShort->Language) AND $dataShort->Language != "N/A") ? M::explodeLanguages($dataShort->Language) : null;
            $movie->countries = (isset($dataShort->Country) AND $dataShort->Country != "N/A") ? M::explodeCountries($dataShort->Country) : null;

            $movie->save();

            $this->console->info("Success insert {$dataShort->imdbID}\n");

        } catch (MassAssignmentException $e) {

            $this->log->error('Movie IMDB', [
                'imdb_id' => $dataShort->imdbID,
                'catch'   => 'MassAssignmentException',
                'message' => $e->getMessage()
            ]);

        } catch (\Symfony\Component\Debug\Exception\FatalErrorException $e) {

            $this->log->error('Movie IMDB', [
                'imdb_id' => $dataShort->imdbID,
                'catch'   => 'FatalErrorException',
                'message' => $e->getMessage()
            ]);

        } catch (\RuntimeException $e) {

            $this->log->error('Movie IMDB', [
                'imdb_id' => $dataShort->imdbID,
                'catch'   => 'RuntimeException',
                'message' => $e->getMessage()
            ]);

        } catch (InvalidArgumentException $e) {
            $this->log->error('Movie IMDB', [
                'imdb_id' => $dataShort->imdbID,
                'catch'   => 'InvalidArgumentException',
                'message' => $e->getMessage()
            ]);
        } /*catch (\Exception $e) {
            $this->log->error('Movie IMDB', [
                'imdb_id' => $dataShort->imdbID,
                'catch'   => 'Exception',
                'message' => $e->getMessage()
            ]);
        }*/

    }
}