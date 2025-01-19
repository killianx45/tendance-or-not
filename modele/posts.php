<?php

require_once "connexion_bdd.php";

class Posts
{
  private $mysqli;

  public function __construct($mysqli)
  {
    $this->mysqli = $mysqli;
  }

  public function getAllPosts()
  {
    $result = $this->mysqli->query("SELECT * FROM posts");
    return $result->fetch_all(MYSQLI_ASSOC);
  }

  public function getPostById($id)
  {
    $result = $this->mysqli->query("SELECT * FROM posts WHERE id=$id");
    if ($result) {
      return $result->fetch_assoc();
    } else {
      error_log("Erreur lors de la récupération des posts : " . $this->mysqli->error);
      return false;
    }
  }

  public function getPostsByIdUser($id_user)
  {
    $res = $this->mysqli->query("SELECT * FROM posts WHERE id_user=$id_user");
    if ($res->num_rows > 0) {
      return $res->fetch_all(MYSQLI_ASSOC);
    } else {
      return "Cet utilisateur n'a pas de posts.";
    }
  }

  public function insertPost($titre, $description, $image, $id_user, $nom_categorie)
  {
    $titre = $this->mysqli->real_escape_string($titre);
    $description = $this->mysqli->real_escape_string($description);
    $image = $this->mysqli->real_escape_string($image);
    $id_user = (int)$id_user;
    $nom_categorie = $this->mysqli->real_escape_string($nom_categorie);

    $query = "INSERT INTO posts (titre, description, image, id_user, nom_categorie) 
                  VALUES ('$titre', '$description', '$image', $id_user, '$nom_categorie')";

    if (!$this->mysqli->query($query)) {
      error_log("Erreur lors de l'insertion du post : " . $this->mysqli->error);
      return false;
    }
    return true;
  }

  public function updatePost($id, $titre, $description, $image, $id_user, $nom_categorie)
  {
    $titre = $this->mysqli->real_escape_string($titre);
    $description = $this->mysqli->real_escape_string($description);
    $image = $this->mysqli->real_escape_string($image);
    $id = (int)$id;
    $updated_at = date('Y-m-d H:i:s');
    $nom_categorie = $this->mysqli->real_escape_string($nom_categorie);

    $query = "SELECT id_user FROM posts WHERE id=$id";
    $result = $this->mysqli->query($query);

    if ($result && $result->num_rows > 0) {
      $post = $result->fetch_assoc();
      if ($post['id_user'] !== $id_user && $_SESSION['role'] !== 'ROLE_ADMIN') {
        echo "Vous n'avez pas les droits pour mettre à jour ce post.";
        return false;
      }
    } else {
      echo "Post introuvable.";
      return false;
    }

    $query = "UPDATE posts 
                  SET titre='$titre', 
                      description='$description', 
                      image='$image', 
                      updated_at='$updated_at',
                      nom_categorie='$nom_categorie'
                  WHERE id=$id";

    if (!$this->mysqli->query($query)) {
      error_log("Erreur lors de la mise à jour du post : " . $this->mysqli->error);
      return false;
    }
    return true;
  }

  public function deletePost($id, $id_user)
  {
    $id = (int)$id;

    $query = "SELECT id_user, image FROM posts WHERE id=$id";
    $result = $this->mysqli->query($query);

    if ($result && $result->num_rows > 0) {
      $post = $result->fetch_assoc();
      if ($post['id_user'] === $id_user || $_SESSION['role'] === 'ROLE_ADMIN') {
        $imagePath = $post['image'];
        if (file_exists($imagePath)) {
          unlink($imagePath);
        }

        $query = "DELETE FROM posts WHERE id=$id";
        if (!$this->mysqli->query($query)) {
          error_log("Erreur lors de la suppression du post : " . $this->mysqli->error);
          return false;
        }

        $query = "DELETE FROM user_post_likes WHERE post_id=$id";
        if (!$this->mysqli->query($query)) {
          error_log("Erreur lors de la suppression des likes/dislikes : " . $this->mysqli->error);
          return false;
        }

        $query = "DELETE FROM commentaires WHERE id_post=$id";
        if (!$this->mysqli->query($query)) {
          error_log("Erreur lors de la suppression des commentaires : " . $this->mysqli->error);
          return false;
        }

        return true;
      } else {
        echo "Vous n'avez pas les droits pour supprimer ce post.";
        return false;
      }
    } else {
      echo "Post introuvable.";
      return false;
    }
  }

  public function getPostsByCategorie($categories)
  {
    if (is_string($categories)) {
      $categories = [$categories];
    }

    $categories = array_filter($categories, 'is_string');
    $categories = array_map(function ($name) {
      return $this->mysqli->real_escape_string($name);
    }, $categories);

    $category_list = "'" . implode("','", $categories) . "'";
    $query = "SELECT posts.*, 
                         (SELECT COUNT(*) FROM user_post_likes WHERE post_id = posts.id AND like_value = 1) AS like_count,
                         (SELECT COUNT(*) FROM user_post_likes WHERE post_id = posts.id AND like_value = -1) AS dislike_count,
                         (SELECT COUNT(*) FROM user_post_likes WHERE post_id = posts.id AND like_value = 1) - 
                         (SELECT COUNT(*) FROM user_post_likes WHERE post_id = posts.id AND like_value = -1) AS coefficient
                  FROM posts 
                  WHERE nom_categorie IN ($category_list) 
                  ORDER BY coefficient DESC";
    $result = $this->mysqli->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
  }
}
