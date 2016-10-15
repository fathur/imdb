<?php

namespace App\Repositories;

use App\Console\Commands\Movie as MovieCommand;
use App\Models\Crew;
use App\Models\Genre;
use App\Models\Movie;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\MassAssignmentException;
use InvalidArgumentException;
use League\Csv\Reader;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class DumpLoader
{
    private $fileLocation;

    private $fileReader;

    private $header;

    private $take;

    private $offset;

    private $chunk = 10;

    private $log;

    /**
     * DumpLoader constructor.
     *
     * @param     $file
     * @param int $offset
     * @param int $take
     *
     * @author  Fathur Rohman <fathur@dragoncapital.center>
     */
    public function __construct($file, $offset = 1, $take = 10)
    {
        if (!ini_get("auto_detect_line_endings")) {
            ini_set("auto_detect_line_endings", '1');
        }

        $this->fileLocation = $file;

        $this->take = $take;
        $this->offset = $offset;
        $this->fileReader = Reader::createFromPath($file);
        $this->fileReader->setDelimiter("\t");

        $this->setHeader();

        $this->log = new Logger(__METHOD__);
//        $this->log->pushHandler(new StreamHandler(storage_path() . '/logs/movie-failed-' . date('Y-m-d') . '.log'));
        $this->log->pushHandler(new StreamHandler(storage_path() . '/logs/movie-failed-' . $this->offset . '-' . $this->take . '-' . date('Y-m-d') . '.log'));
    }

    /**
     * @return mixed
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * array:22 [▼
     * 0 => "ID"
     * 1 => "imdbID"
     * 2 => "Title"
     * 3 => "Year"
     * 4 => "Rating"
     * 5 => "Runtime"
     * 6 => "Genre"
     * 7 => "Released"
     * 8 => "Director"
     * 9 => "Writer"
     * 10 => "Cast"
     * 11 => "Metacritic"
     * 12 => "imdbRating"
     * 13 => "imdbVotes"
     * 14 => "Poster"
     * 15 => "Plot"
     * 16 => "FullPlot"
     * 17 => "Language"
     * 18 => "Country"
     * 19 => "Awards"
     * 20 => "lastUpdated"
     * 21 => "Type"
     * ]
     *
     * @author  Fathur Rohman <fathur@dragoncapital.center>
     */
    public function setHeader()
    {
        $this->header = $this->fileReader->fetchOne();
    }

    /**
     *
     * @author  Fathur Rohman <fathur@dragoncapital.center>
     */
    public function saveToDatabase($console = null)
    {
        for ($i = 0; $i < $this->take / $this->chunk; $i ++) {

            $csvData = $this->fileReader
                ->setOffset(($i * $this->chunk) + $this->offset)
                ->setLimit($this->chunk)
                ->fetchAll();

            $k = ($i * $this->chunk) + $this->offset;
            foreach ($csvData as $data) {

                if ($console instanceof MovieCommand)
                    $console->info($this->fileLocation . " begin in line {$k}");

                $k ++;

                try {

                    if (Movie::where('imdb_id', $data[1])->first()) {
                        continue;
                    }

                    $dateLastUpdateExplode = explode('.', $data[20]);
                    $lastUpdate = $dateLastUpdateExplode[0];

                    $movie = new Movie();
                    $movie->imdb_id = isset($data[1]) ? $data[1] : null;
                    $movie->title = isset($data[2]) ? utf8_encode($data[2]) : null;
                    $movie->year = isset($data[3]) ? (int)$data[3] : null;
                    $movie->rated = isset($data[4]) ? self::changeEmptyStringToNull($data[4]) : null;
                    $movie->released = self::toTimestamp($data[7]);
                    $movie->runtime = self::toMiliSeconds($data[5]);
                    $movie->genres = isset($data[6]) ? self::explodeGenres($data[6]) : null;
                    $movie->directors = isset($data[8]) ? self::explodeDirectors($data[8]) : null;
                    $movie->writers = isset($data[9]) ? self::explodeWriters($data[9]) : null;
                    $movie->actors = isset($data[10]) ? self::explodeActors($data[10]) : null;
                    $movie->plot = [
                        'short' => isset($data[15]) ? utf8_encode($data[15]) : null,
                        'full'  => isset($data[16]) ? utf8_encode($data[16]) : null
                    ];

                    $movie->awards = isset($data[19]) ? self::changeEmptyStringToNull(utf8_encode($data[19])) : null;
                    $movie->poster = isset($data[14]) ? self::changeEmptyStringToNull($data[14]) : null;
                    $movie->metascore = isset($data[11]) ? self::changeEmptyStringToNull($data[11]) : null;
                    $movie->imdb_rating = isset($data[12]) ? floatval($data[12]) : null;
                    $movie->imdb_votes = isset($data[13]) ? (int)$data[13] : null;
                    $movie->type = isset($data[21]) ? utf8_encode($data[21]) : null;
                    $movie->last_update = $lastUpdate;
                    $movie->languages = isset($data[17]) ? $this::explodeLanguages($data[17]) : null;
                    $movie->countries = isset($data[18]) ? $this::explodeCountries($data[18]) : null;

                    if ($movie->save()) {

                        $this->saveGenres($data);
                        $this->saveDirectors($data);
                        $this->saveWriters($data);
                        $this->saveActors($data);
                    }

                } catch (MassAssignmentException $e) {

                    $this->log->error('Movie IMDB', [
                        'imdb_id' => $data[1],
                        'catch'   => 'MassAssignmentException',
                        'message' => $e->getMessage()
                    ]);

                } catch (\Symfony\Component\Debug\Exception\FatalErrorException $e) {

                    $this->log->error('Movie IMDB', [
                        'imdb_id' => $data[1],
                        'catch'   => 'FatalErrorException',
                        'message' => $e->getMessage()
                    ]);

                } catch (\RuntimeException $e) {

                    $this->log->error('Movie IMDB', [
                        'imdb_id' => $data[1],
                        'catch'   => 'RuntimeException',
                        'message' => $e->getMessage()
                    ]);

                } catch (InvalidArgumentException $e) {
                    $this->log->error('Movie IMDB', [
                        'imdb_id' => $data[1],
                        'catch'   => 'InvalidArgumentException',
                        'message' => $e->getMessage()
                    ]);
                } catch (\Exception $e) {
                    $this->log->error('Movie IMDB', [
                        'imdb_id' => $data[1],
                        'catch'   => 'Exception',
                        'message' => $e->getMessage()
                    ]);
                }

                if ($console instanceof MovieCommand)
                    $console->info($this->fileLocation . " end in line " . (intval($k) - 1));

            }

        }

    }

    /**
     * @return int
     *
     * @author  Fathur Rohman <fathur@dragoncapital.center>
     */
    public function getTake()
    {
        return $this->take;
    }

    /**
     * @param int $take
     *
     * @author  Fathur Rohman <fathur@dragoncapital.center>
     */
    public function setTake($take)
    {
        $this->take = $take;
    }

    /**
     * @param $movie
     * @param $data
     *
     * @author  Fathur Rohman <fathur@dragoncapital.center>
     */
    private function saveGenres($data)
    {
        $genres = explode(',', $data[6]);

        array_map(function ($item) {

            $genre = Genre::where('genre', trim($item));

            if ($genre->count() > 0) {
                return $genre->first()->id;
            } else {

                if ($item != '') {

                    $newGenre = Genre::create([
                        'genre' => utf8_encode(trim($item))
                    ]);

                    return $newGenre->id;
                }

                return null;
            }

        }, $genres);

    }

    /**
     * @param $movie
     * @param $data
     */
    private function saveDirectors($data)
    {
        $this->saveCrews($data[8]);
    }

    /**
     * @param $movie
     * @param $data
     */
    private function saveWriters($data)
    {
        $this->saveCrews($data[9]);
    }

    /**
     * @param $movie
     * @param $data
     */
    private function saveActors($data)
    {
        $this->saveCrews($data[10]);
    }

    /**
     * @param $movie
     * @param $data
     */
    private function saveCrews($data)
    {
        $crews = explode(',', $data);

        array_map(function ($item) {

            // mendapatkan kata yang di dalam kurung, semuanya!!
            preg_match_all('/\(.*?\)/', $item, $parenthesis);

            // get name, yang ga ada di dalam kurung, dengan cara mengganti yang ada
            // kurungnya dengan string kosong
            $name = str_replace($parenthesis[0], '', $item);

            // remove multiple space in this sentence
            $name = preg_replace('!\s+!', ' ', trim($name));

            $crew = Crew::where('name', $name);

            if ($crew->count() == 0) {

                if ($name != '') {

                    $newCrew = Crew::create([
                        'name' => utf8_encode($name)
                    ]);

                }

                return null;
            }
        }, $crews);

    }

    /**
     * @param $runtime
     *
     * @return int
     */
    private static function toMiliSeconds($runtime)
    {
        $runtimeMinutes = explode(' ', $runtime);

        return (int)$runtimeMinutes[0];
    }

    /**
     * @param $released
     *
     * @return string
     */
    private static function toTimestamp($released)
    {
        return Carbon::parse($released)->toDateTimeString();
    }

    /**
     * @param $countries
     *
     * @return array
     */
    private static function explodeCountries($countries)
    {
        return self::explodeData($countries);

    }

    /**
     * @param $languages
     *
     * @return array
     */
    private static function explodeLanguages($languages)
    {
        return self::explodeData($languages);
    }

    /**
     * @param $genres
     *
     * @return array
     */
    private static function explodeGenres($genres)
    {
        return self::explodeData($genres);
    }

    /**
     * @param $directors
     *
     * @return array
     */
    private static function explodeDirectors($directors)
    {
        return self::explodeData($directors);

    }

    /**
     * @param $actors
     *
     * @return array
     */
    private static function explodeActors($actors)
    {
        return self::explodeData($actors);

    }

    /**
     * @param $writers
     *
     * @return array
     */
    private static function explodeWriters($writers)
    {
        $writers = explode(',', $writers);

        $writers = array_filter($writers, function ($writer) {

            if ($writer == "" || is_null($writer)) {
                return false;
            }

            return true;
        });

        $writers = array_map(function ($item) {

            // mendapatkan kata yang di dalam kurung, semuanya!!
            preg_match_all('/\(.*?\)/', $item, $parenthesis);

            // get name, yang ga ada di dalam kurung, dengan cara mengganti yang ada
            // kurungnya dengan string kosong
            $name = str_replace($parenthesis[0], '', $item);

            // remove multiple space in this sentence
            $name = preg_replace('!\s+!', ' ', trim($name));

            return [

                'name' => $name,
                'as'   => isset($parenthesis[0][0]) ? substr($parenthesis[0][0], 1, - 1) : null
            ];

        }, $writers);

        return $writers;
    }

    /**
     * @param $genres
     *
     * @return array
     */
    private static function explodeData($data)
    {
        $data = explode(',', $data);

        $data = array_filter($data, function ($datum) {

            if ($datum == "" || is_null($datum)) {
                return false;
            }

            return true;
        });

        $data = array_map(function ($datum) {
            return utf8_encode(trim($datum));
        }, $data);

        return $data;
    }

    private static function changeEmptyStringToNull($string)
    {
        if ($string == "")
            return null;
    }
}