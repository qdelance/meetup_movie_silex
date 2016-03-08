<?php

namespace Meetup;

/**
 * Class MyObjectManager
 * Simple class to store data, used to show how to use low level calls to DBAL
 * This is done as use of Doctrine ORM requires third party provider not in default Silex distrib
 * IMO, if you really want to use ORM, you should use Symfony rather than Silex...
 * @package Meetup
 */
class MyObjectManager
{

    // Nb of items in paginated views
    const NB_PER_PAGE = 25;

    // DBAL
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function findMovieById($id)
    {
        $sql = "SELECT m.id, title, t.name as type, releaseDate, year, rating FROM movie m LEFT JOIN type t ON m.type_id=t.id WHERE m.id = ?";
        $movie = $this->db->fetchAssoc($sql, array((int)$id));

        return $movie;
    }

    public function findAllMovies()
    {
        $sql = "SELECT m.id, title, t.name as type, releaseDate, year, rating FROM movie m LEFT JOIN type t ON m.type_id=t.id ORDER BY title";
        $movies = $this->db->fetchAll($sql);

        return $movies;
    }

    public function findMovieCount()
    {
        $statement = $this->db->prepare("SELECT count(*) FROM movie m LEFT JOIN type t ON m.type_id=t.id");
        $statement->execute();
        $count = $statement->fetchColumn(0);

        return $count;
    }

    public function findAllMoviesPaginated($page, $limit = self::NB_PER_PAGE)
    {
        $statement = $this->db->prepare(
          /*"SELECT m.id, title, t.name as type, releaseDate, year, rating FROM movie m LEFT JOIN type t ON m.type_id=t.id LIMIT :offset , :limit"*/
          "SELECT m.id, title, t.name as type, releaseDate, year, rating FROM movie m LEFT JOIN type t ON m.type_id=t.id ORDER BY title LIMIT :limit OFFSET :offset"
        );
        $statement->bindValue('limit', $limit, \PDO::PARAM_INT);
        $statement->bindValue('offset', ($page - 1) * $limit, \PDO::PARAM_INT);
        $statement->execute();

        $movies = $statement->fetchAll();

        return $movies;
    }

    // Very weak, would need some checks
    public function persistMovie(array $movie)
    {
        if ($movie != null) {
            if (array_key_exists('id', $movie)) {
                $statement = $this->db->prepare(
                  "UPDATE movie SET title = :title, year = :year, releaseDate = :releaseDate, rating = :rating WHERE id = :id"
                );

                $statement->bindValue('title', $movie['title']);
                $statement->bindValue('year', $movie['year']);
                // http://doctrine-orm.readthedocs.org/projects/doctrine-dbal/en/latest/reference/data-retrieval-and-manipulation.html#doctrinedbaltypes-conversion
                $statement->bindValue('releaseDate', $movie['releaseDate'], 'date');
                $statement->bindValue('rating', $movie['rating']);
                $statement->bindValue('id', $movie['id']);

                $statement->execute();

            } else {
                $statement = $this->db->prepare(
                  "INSERT INTO movie (title, year, releaseDate, rating) VALUES (:title, :year, :releaseDate, :rating)"
                );

                $statement->bindValue('title', $movie['title']);
                $statement->bindValue('year', $movie['year']);
                $statement->bindValue('releaseDate', $movie['releaseDate'], 'date');
                $statement->bindValue('rating', $movie['rating']);

                $statement->execute();
            }
        }
    }

    public function removeMovie($id)
    {
        if ($id != null) {
            $sql = "DELETE FROM movie WHERE id = ?";
            $this->db->executeUpdate($sql, array((int)$id));
        }
    }

}
