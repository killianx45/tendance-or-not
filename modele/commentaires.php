<?php

require_once "connexion_bdd.php";

class Commentaires
{
    private $mysqli;

    public function __construct($mysqli)
    {
        $this->mysqli = $mysqli;
    }

    public function getAllCommentairesByPostId($id_post)
    {
        $res = $this->mysqli->query("SELECT * FROM commentaires WHERE id_post=$id_post");
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    public function getCommentaireById($id)
    {
        $res = $this->mysqli->query("SELECT * FROM commentaires WHERE id=$id");
        return $res->fetch_assoc();
    }

    public function insertCommentaire($commentaire, $id_post, $id_user)
    {
        $commentaire = $this->mysqli->real_escape_string($commentaire);
        $id_post = (int)$id_post;
        $id_user = (int)$id_user;

        $query = "INSERT INTO commentaires (contenu, id_post, id_user) VALUES ('$commentaire', $id_post, $id_user)";

        if (!$this->mysqli->query($query)) {
            error_log("Erreur lors de l'insertion du commentaire : " . $this->mysqli->error);
            return false;
        }
        return true;
    }

    public function deleteCommentaire($id)
    {
        $commentaire = $this->getCommentaireById($id);
        if ($_SESSION['role'] === 'ROLE_ADMIN' || $_SESSION['id'] === $commentaire['id_user']) {
            $query = "DELETE FROM commentaires WHERE id=$id";
            if (!$this->mysqli->query($query)) {
                error_log("Erreur lors de la suppression du commentaire : " . $this->mysqli->error);
                return false;
            }
            header('Location: /sae_301/');
        } else {
            echo "Vous n'avez pas les droits pour supprimer ce commentaire.";
            return false;
        }
    }

    public function getCommentairesByUser($id_user)
    {
        $res = $this->mysqli->query("SELECT * FROM commentaires WHERE id_user=$id_user");
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    public function getCommentairesByIdPost($postId)
    {
        if ($postId === NULL) {
            throw new InvalidArgumentException("L'identifiant du post ne peut pas être null.");
        }
        $query = "SELECT c.*, u.pseudo FROM commentaires c JOIN users u ON c.id_user = u.id WHERE c.id_post = $postId";
        $result = $this->mysqli->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getPostsByCommentaires($id_user)
    {
        $query = "SELECT p.*, c.id_post FROM posts p JOIN commentaires c ON p.id = c.id_post WHERE c.id_user = $id_user";
        $result = $this->mysqli->query($query);
        $posts = [];
        while ($row = $result->fetch_assoc()) {
            $posts[$row['id_post']] = $row;
        }
        return $posts;
    }
}
