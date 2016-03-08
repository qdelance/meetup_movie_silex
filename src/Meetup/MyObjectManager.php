<?php

namespace Meetup;

class MyObjectManager
{
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
        $sql = "SELECT m.id, title, t.name as type, releaseDate, year, rating FROM movie m LEFT JOIN type t ON m.type_id=t.id LIMIT 100";
        $movies = $this->db->fetchAll($sql);

        return $movies;
    }

    // Very weak, would need some checks
    public function persistMovie(array $movie)
    {
        if ($movie != null) {
            if (array_key_exists('id', $movie)) {
                $qb = $this->db->createQueryBuilder();
                $sql = "UPDATE movie SET title = ?, year = ?, releaseDate = ?, rating = ? WHERE id = ?";
                $this->db->executeUpdate(
                  $sql,
                  array(
                    $movie['title'],
                    $movie['year'],
                    $movie['releaseDate'],
                    $movie['rating'],
                    $movie['id'],
                  )
                );
            } else {
                $sql = "INSERT INTO movie (title, year, releaseDate, rating) VALUES (?, ?, ?, ?)";
                $this->db->executeUpdate(
                  $sql,
                  array(
                    $movie['title'],
                    $movie['year'],
                    $movie['releaseDate'],
                    $movie['rating'],
                  )
                );
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
