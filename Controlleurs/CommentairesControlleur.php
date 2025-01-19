<?php

require_once __DIR__ . '/../bootsrap.php';
require_once __DIR__ . '/../modele/Commentaires.php';

class CommentairesControlleur
{
    private $twig;
    private $commentairesModel;

    public function __construct($twig, $mysqli)
    {
        $this->twig = $twig;
        $this->commentairesModel = new Commentaires($mysqli);
    }

    public function listerCommentaires($id)
    {
        $commentaires = $this->commentairesModel->getAllCommentairesByPostId($id);
        echo $this->twig->render('template-commentaires-post.twig.html', ['commentaires' => $commentaires]);
    }

    public function getFormCreateCommentaire($id)
    {
        echo $this->twig->render('template-create-commentaire.twig.html', ['id' => $id]);
    }

    public function storeCommentaire()
    {
        if (isset($_POST['commentaire'], $_POST['id_post'], $_POST['id_user'])) {
            $commentaire = htmlspecialchars($_POST['commentaire']);
            $id_post = htmlspecialchars($_POST['id_post']);
            $id_user = htmlspecialchars($_POST['id_user']);

            error_log("Commentaire: $commentaire, ID Post: $id_post, ID Utilisateur: $id_user");

            if ($this->commentairesModel->insertCommentaire($commentaire, $id_post, $id_user)) {
                header('Location: /sae_301/');
                exit();
            } else {
                echo "Erreur lors de l'insertion du commentaire.";
            }
        } else {
            echo "Données manquantes.";
        }
    }

    public function destroyCommentaire($id)
    {
        if (!isset($_SESSION['id'])) {
            header('Location: connexion_user.php');
            exit();
        }

        $commentaire = $this->commentairesModel->getCommentaireById($id);
        if ($commentaire) {
            if ($_SESSION['role'] === 'ROLE_ADMIN' || $_SESSION['id'] === $commentaire['id_user']) {
                $this->commentairesModel->deleteCommentaire($id);
                header('Location: /sae_301/');
                exit();
            } else {
                echo "Vous n'avez pas les droits pour supprimer ce commentaire.";
            }
        } else {
            echo "Commentaire introuvable.";
        }
    }

    public function getCommentairesByIdUser($id_user)
    {
        $commentaires = $this->commentairesModel->getCommentairesByUser($id_user);
        return $commentaires;
    }

    public function getCommentaireById($id)
    {
        return $this->commentairesModel->getCommentaireById($id);
    }

    public function getCommentairesByIdPost($id_post)
    {
        return $this->commentairesModel->getCommentairesByIdPost($id_post);
    }

    public function getPostsByCommentaires($id_user)
    {
        return $this->commentairesModel->getPostsByCommentaires($id_user);
    }
}
