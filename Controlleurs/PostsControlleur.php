<?php

require_once __DIR__ . '/../bootsrap.php';
require_once __DIR__ . '/../modele/Posts.php';
require_once __DIR__ . '/../modele/Users.php';

class PostsControlleur
{
    private $twig;
    private $postsModel;
    private $usersModel;

    public function __construct($twig, $mysqli)
    {
        $this->twig = $twig;
        $this->postsModel = new Posts($mysqli);
        $this->usersModel = new Users($mysqli);
    }

    public function listerPosts()
    {
        $posts = $this->postsModel->getAllPosts();
        echo $this->twig->render('template-liste-posts.twig.html', ['posts' => $posts]);
    }

    public function listerPostsUser($id)
    {
        $posts_added = $this->postsModel->getPostsByIdUser($id);
        if ($posts_added) {
            echo $this->twig->render('template-liste-posts-user.twig.html', ['user_posts' => $posts_added]);
        } else {
            echo "Aucun post trouvé pour cet utilisateur.";
        }
    }

    public function getFormCreatePost($categories)
    {
        echo $this->twig->render('template-create-post.twig.html', ['categories' => $categories]);
    }

    public function storePost()
    {
        if (!isset($_SESSION['id'])) {
            header('Location: connexion_user.php');
            exit();
        }
        $titre = htmlspecialchars($_POST['titre'] ?? '');
        $description = htmlspecialchars($_POST['description'] ?? '');
        $id_user = $_SESSION['id'];
        $nom_categorie = htmlspecialchars($_POST['nom_categorie'] ?? '');
        $imagePath = $this->telechargementImages($_FILES['image'] ?? null, $id_user);

        if ($imagePath !== false) {
            $this->postsModel->insertPost($titre, $description, $imagePath, $id_user, $nom_categorie);
            header('Location: /sae_301/');
        } else {
            echo "Erreur lors de l'upload de l'image.";
        }
    }

    private function telechargementImages($fichier, $user_id)
    {
        if (isset($fichier) && $fichier['error'] === UPLOAD_ERR_OK) {
            return $this->processusImages($fichier, $user_id);
        } else {
            echo "Erreur lors de l'upload de l'image : " . ($fichier['error'] ?? 'Aucune image téléchargée.');
            return false;
        }
    }

    public function processusImages($fichier, $user_id)
    {
        $imageTmpPath = $fichier['tmp_name'];
        $imageName = $fichier['name'];
        $imageName = strtolower(str_replace(' ', '-', $imageName));
        $salt = uniqid($user_id . '-', true);
        $hashedName = hash('sha256', $salt . $imageName);
        $imagePath = 'images/' . $hashedName . '.webp';
        $fichierType = mime_content_type($imageTmpPath);
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

        if (in_array($fichierType, $allowedTypes)) {
            $image = null;
            switch ($fichierType) {
                case 'image/jpeg':
                    $image = imagecreatefromjpeg($imageTmpPath);
                    break;
                case 'image/png':
                    $image = imagecreatefrompng($imageTmpPath);
                    break;
                case 'image/gif':
                    $image = imagecreatefromgif($imageTmpPath);
                    break;
            }

            if ($image) {
                $maxWidth = 800;
                $maxHeight = 800;
                list($width, $height) = getimagesize($imageTmpPath);
                $ratio = min($maxWidth / $width, $maxHeight / $height);
                $newWidth = (int)($width * $ratio);
                $newHeight = (int)($height * $ratio);
                $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
                imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                if (imagewebp($resizedImage, $imagePath)) {
                    imagedestroy($image);
                    imagedestroy($resizedImage);
                    return $imagePath;
                } else {
                    echo "Erreur lors de la conversion de l'image en WebP.";
                    return false;
                }
            } else {
                echo "Erreur lors de la création de l'image.";
                return false;
            }
        } else {
            echo "Le fichier téléchargé n'est pas une image valide.";
            return false;
        }
    }

    public function getFormUpdatePost($id, $categories)
    {
        if (!isset($_SESSION['id'])) {
            header('Location: connexion_user.php');
            exit();
        }

        $post = $this->postsModel->getPostById($id);
        if ($post) {
            echo $this->twig->render('template-update-post.twig.html', ['post' => $post, 'categories' => $categories]);
        } else {
            echo "Erreur lors de la récupération du post";
        }
    }

    public function update($id)
    {
        if (!isset($_SESSION['id'])) {
            header('Location: connexion_user.php');
            exit();
        }

        $post = $this->postsModel->getPostById($id);
        if ($post) {
            if ($post['id_user'] !== $_SESSION['id']) {
                echo "Vous n'avez pas les droits pour mettre à jour ce post.";
                return;
            }

            $titre = htmlspecialchars($_POST['titre'] ?? $post['titre']);
            $description = htmlspecialchars($_POST['description'] ?? $post['description']);
            $nom_categorie = htmlspecialchars($_POST['nom_categorie'] ?? $post['nom_categorie']);
            $imagePath = $post['image'];

            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $imagePath = $this->telechargementImages($_FILES['image'], $_SESSION['id']);
                if ($imagePath === false) {
                    return;
                }
            }

            $this->postsModel->updatePost($id, $titre, $description, $imagePath, $_SESSION['id'], $nom_categorie);
            header('Location: /sae_301/');
            exit();
        } else {
            echo "Post introuvable.";
        }
    }

    public function destroyPost($id, $id_user)
    {
        if (!isset($_SESSION['id'])) {
            header('Location: connexion_user.php');
            exit();
        }

        $post = $this->postsModel->getPostById($id);
        if ($post) {
            if ($_SESSION['id'] === $id_user || $_SESSION['role'] === 'ROLE_ADMIN') {
                return $this->postsModel->deletePost($id, $id_user);
            }
        }
        return false;
    }

    public function postCategorie($nom_categorie)
    {
        $posts = $this->postsModel->getPostsByCategorie($nom_categorie);
        return $posts;
    }

    public function getPostsByCategorie($categories)
    {
        return $this->postsModel->getPostsByCategorie($categories);
    }

    public function getPostsByUserId($id_user)
    {
        return $this->postsModel->getPostsByIdUser($id_user);
    }

    public function getPostById($id)
    {
        return $this->postsModel->getPostById($id);
    }
}
