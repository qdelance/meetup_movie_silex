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
        $sql = "SELECT m.id, title, type_id, t.name as type, releaseDate, year, rating FROM movie m LEFT JOIN type t ON m.type_id=t.id WHERE m.id = ?";
        $movie = $this->db->fetchAssoc($sql, array((int)$id));

        $statement = $this->db->prepare("SELECT id, name FROM genre g INNER JOIN movie_genre mg ON g.id = mg.genre_id WHERE mg.movie_id = :id");
        $statement->bindValue('id', $id, \PDO::PARAM_INT);
        $statement->execute();
        $rows = $statement->fetchAll();
        foreach ($rows as $row)
        {
            $movie['genres_id'][] = $row['id'];
            $movie['genres'][] = $row['name'];
        }


        return $movie;
    }

    public function findAllTypes()
    {
        $sql = "SELECT id, name FROM type";
        $types = $this->db->fetchAll($sql);

        return $types;
    }

    public function findAllGenres()
    {
        $sql = "SELECT id, name FROM genre";
        $genres = $this->db->fetchAll($sql);

        return $genres;
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
        // GROUP_CONCAT is not portable, but this request avoid costly rehydratation as we do not use Doctrine ORM
        $statement = $this->db->prepare(
          "SELECT m.id, title, t.name as type, group_concat(g.name) genres, releaseDate, year, rating FROM movie m LEFT JOIN type t ON m.type_id=t.id LEFT JOIN movie_genre mg on mg.movie_id=m.id left join genre g on g.id=mg.genre_id group by m.id ORDER BY title LIMIT :limit OFFSET :offset"
        );
        $statement->bindValue('limit', $limit, \PDO::PARAM_INT);
        $statement->bindValue('offset', ($page - 1) * $limit, \PDO::PARAM_INT);
        $statement->execute();

        $movies = $statement->fetchAll();

        return $movies;
    }

    public function findBestRated($page, $limit)
    {
        // GROUP_CONCAT is not portable, but this request avoid costly rehydratation as we do not use Doctrine ORM
        $statement = $this->db->prepare(
          "SELECT m.id, title, t.name as type, releaseDate, year, rating FROM movie m LEFT JOIN type t ON m.type_id=t.id ORDER BY rating DESC LIMIT :limit OFFSET :offset"
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
                  "UPDATE movie SET title = :title, type_id = :type_id, year = :year, releaseDate = :releaseDate, rating = :rating WHERE id = :id"
                );

                $statement->bindValue('title', $movie['title']);
                $statement->bindValue('type_id', $movie['type_id']);
                $statement->bindValue('year', $movie['year']);
                // http://doctrine-orm.readthedocs.org/projects/doctrine-dbal/en/latest/reference/data-retrieval-and-manipulation.html#doctrinedbaltypes-conversion
                $statement->bindValue('releaseDate', $movie['releaseDate'], 'date');
                $statement->bindValue('rating', $movie['rating']);
                $statement->bindValue('id', $movie['id']);

                $statement->execute();

                // Delete existing links to genres
                $statement = $this->db->prepare(
                  "DELETE FROM movie_genre WHERE movie_id = :movie_id"
                );
                $statement->bindValue('movie_id', $movie['id']);
                $statement->execute();

                // Recreate new links
                foreach ($movie['genres_id'] as $genre_id)
                {
                    $statement = $this->db->prepare(
                      "INSERT INTO movie_genre (movie_id, genre_id) VALUES (:movie_id, :genre_id)"
                    );
                    $statement->bindValue('movie_id', $movie['id']);
                    $statement->bindValue('genre_id', $genre_id);
                    $statement->execute();
                }


            } else {
                $statement = $this->db->prepare(
                  "INSERT INTO movie (title, type_id, year, releaseDate, rating) VALUES (:title, :type_id, :year, :releaseDate, :rating)"
                );

                $statement->bindValue('title', $movie['title']);
                $statement->bindValue('type_id', $movie['type_id']);
                $statement->bindValue('year', $movie['year']);
                $statement->bindValue('releaseDate', $movie['releaseDate'], 'date');
                $statement->bindValue('rating', $movie['rating']);

                $statement->execute();

                $id = $this->db->lastInsertId();
                foreach ($movie['genres_id'] as $genre_id)
                {
                    $statement = $this->db->prepare(
                      "INSERT INTO movie_genre (movie_id, genre_id) VALUES (:movie_id, :genre_id)"
                    );
                    $statement->bindValue('movie_id', $id);
                    $statement->bindValue('genre_id', $genre_id);
                    $statement->execute();
                }

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
