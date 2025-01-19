<?php

require_once "connexion_bdd.php";

class Users
{
  private $mysqli;

  public function __construct($mysqli)
  {
    $this->mysqli = $mysqli;
  }

  public function getAllUsers()
  {
    $result = $this->mysqli->query("SELECT * FROM users");
    return $result->fetch_all(MYSQLI_ASSOC);
  }

  public function getUserById($id)
  {
    $res = $this->mysqli->query("SELECT * FROM users WHERE id=$id");
    return $res->fetch_assoc();
  }

  public function insertUser($nom, $pseudo, $email, $password, $age)
  {
    $nom = $this->mysqli->real_escape_string($nom);
    $pseudo = $this->mysqli->real_escape_string($pseudo);
    $email = $this->mysqli->real_escape_string($email);

    $query = "INSERT INTO users (nom, pseudo, email, password, age) 
                VALUES ('$nom', '$pseudo', '$email', '$password', $age)";

    if ($this->mysqli->query($query)) {
      return $this->mysqli->insert_id; // Retourne l'ID du nouvel utilisateur
    }
    return false;
  }

  public function getUserByEmail($email)
  {
    $res = $this->mysqli->query("SELECT * FROM users WHERE email='$email'");
    return $res->fetch_assoc();
  }

  public function deleteUser($id)
  {
    $query = "DELETE FROM user_categories WHERE id_user = $id";
    $this->mysqli->query($query);

    $query = "DELETE FROM user_post_likes WHERE user_id = $id";
    $this->mysqli->query($query);

    $query = "DELETE FROM commentaires WHERE id_user = $id";
    $this->mysqli->query($query);

    $query = "DELETE FROM users WHERE id = $id";
    $this->mysqli->query($query);

    header('Location: /sae_301/');
  }

  public function updateUserPostLike($user_id, $post_id, $like_value)
  {
    $query = "SELECT * FROM user_post_likes WHERE user_id = $user_id AND post_id = $post_id";
    $result = $this->mysqli->query($query);

    if ($result->num_rows > 0) {
      $query = "UPDATE user_post_likes SET like_value = $like_value WHERE user_id = $user_id AND post_id = $post_id";
    } else {
      $query = "INSERT INTO user_post_likes (user_id, post_id, like_value) VALUES ($user_id, $post_id, $like_value)";
    }

    if (!$this->mysqli->query($query)) {
      error_log("Erreur lors de la mise à jour du like/dislike : " . $this->mysqli->error);
      return false;
    }
    return true;
  }

  public function getPostsLikeByUser($user_id)
  {
    $query = "SELECT posts.* FROM posts 
                  JOIN user_post_likes ON posts.id = user_post_likes.post_id 
                  WHERE user_post_likes.user_id = $user_id AND user_post_likes.like_value = 1";
    $result = $this->mysqli->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
  }

  public function getPostsDislikeByUser($user_id)
  {
    $query = "SELECT posts.* FROM posts 
                  JOIN user_post_likes ON posts.id = user_post_likes.post_id 
                  WHERE user_post_likes.user_id = $user_id AND user_post_likes.like_value = -1";
    $result = $this->mysqli->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
  }

  public function getLikeStatus($user_id, $post_id)
  {
    $query = "SELECT like_value FROM user_post_likes WHERE user_id = $user_id AND post_id = $post_id";
    $result = $this->mysqli->query($query);
    return $result->fetch_assoc()['like_value'] ?? 0;
  }

  public function getFavoriteCategories($id_user)
  {
    $query = "SELECT c.* FROM categories c
                  JOIN user_categories uc ON c.id = uc.id_categorie
                  WHERE uc.id_user = $id_user";
    $result = $this->mysqli->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
  }

  public function addFavoriteCategorie($id_user, $id_categorie)
  {
    $query = "INSERT INTO user_categories (id_user, id_categorie) VALUES (?, ?)";
    $stmt = $this->mysqli->prepare($query);
    $stmt->bind_param("ii", $id_user, $id_categorie);
    return $stmt->execute();
  }

  public function storeVerificationToken($userId, $token)
  {
    $stmt = $this->mysqli->prepare("UPDATE users SET verification_token = ? WHERE id = ?");
    $stmt->bind_param("si", $token, $userId);
    return $stmt->execute();
  }


  public function verifyEmail($token)
  {
    $stmt = $this->mysqli->prepare("UPDATE users SET is_verified = 1 WHERE verification_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    return $stmt->affected_rows > 0;
  }
}
