<?php

require_once "connexion_bdd.php";

class Categories
{
    private $mysqli;

    public function __construct($mysqli)
    {
        $this->mysqli = $mysqli;
    }

    public function getAllCategories()
    {
        $result = $this->mysqli->query("SELECT * FROM categories");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getCategoryById($id)
    {
        $query = "SELECT * FROM categories WHERE id = $id";
        $result = $this->mysqli->query($query);
        return $result->fetch_assoc();
    }

    public function insertCategorie($nom)
    {
        if ($_SESSION['role'] !== 'ROLE_ADMIN') {
            echo "Vous n'avez pas les droits pour ajouter une catégorie.";
            return false;
        }
        $nom = $this->mysqli->real_escape_string($nom);
        $query = "INSERT INTO categories (nom) VALUES ('$nom')";
        return $this->mysqli->query($query);
    }

    public function deleteCategorie($id)
    {
        if ($_SESSION['role'] !== 'ROLE_ADMIN') {
            echo "Vous n'avez pas les droits pour supprimer une catégorie.";
            return false;
        }
        $query_posts = "DELETE FROM posts WHERE nom_categorie = (SELECT nom FROM categories WHERE id = $id)";
        $this->mysqli->query($query_posts);

        $query = "DELETE FROM categories WHERE id = $id";
        return $this->mysqli->query($query);
    }

    public function getCategoryByName($nom)
    {
        $nom = $this->mysqli->real_escape_string($nom);
        $query = "SELECT * FROM categories WHERE nom = '$nom'";
        $result = $this->mysqli->query($query);
        return $result->fetch_assoc();
    }
}
