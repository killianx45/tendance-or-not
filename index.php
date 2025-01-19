<?php
session_start();

require_once "config.php";
require_once "connexion_bdd.php";
require_once "bootsrap.php";
require_once "Controlleurs/UsersControlleur.php";
require_once "Controlleurs/PostsControlleur.php";
require_once "Controlleurs/CommentairesControlleur.php";
require_once "Controlleurs/CategoriesControlleur.php";

$usersController = new UsersControlleur($twig, $mysqli);
$postsController = new PostsControlleur($twig, $mysqli);
$commentairesController = new CommentairesControlleur($twig, $mysqli);
$categoriesController = new CategoriesControlleur($twig, $mysqli);


if (!isset($_SESSION['is_connected']) || $_SESSION['is_connected'] === false) {
    if (isset($_GET['page']) && $_GET['page'] === 'inscription_user') {
        require "inscription_user.php";
        exit();
    }
    header('Location: connexion_user.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['name'])) {
        switch ($_POST['name']) {
            case 'store_post':
                $postsController->storePost();
                break;
            case 'store_user':
                $usersController->storeUser();
                exit();
            case 'store_categorie':
                $categoriesController->storeCategorie();
                break;
            default:
                echo "Rôle non reconnu.";
                exit();
        }
    } elseif (isset($_POST['_method']) && $_POST['_method'] === 'PUT') {
        $id = $_GET['id'];
        $postsController->update($id);
    }
}

if ($_SESSION['role'] === 'ROLE_ADMIN') {
    echo $twig->render('template-backoffice.twig.html');
} elseif ($_SESSION['role'] === 'ROLE_USER') {
    if (isset($_GET['page'])) {
        switch ($_GET['page']) {
            case 'add-favorite-categorie':
                $usersController->addFavoriteCategories($_SESSION['id'], $_GET['id']);
                break;
            default:
                break;
        }
    } else {
        $categories = $categoriesController->getAllCategories();
        require "template-frontoffice/template-frontoffice.php";
    }
} else {
    echo "Rôle non reconnu.";
    exit();
}

if (isset($_GET['page'])) {
    switch ($_GET['page']) {
        case 'store_commentaire':
            $commentairesController->storeCommentaire();
            break;
        case 'users':
            $usersController->listerUsers();
            break;
        case 'create-user':
            if ($_SESSION['role'] === 'ROLE_ADMIN') {
                $usersController->getFormCreateUser();
            }
            break;
        case 'user-posts':
            if (isset($_GET['id'])) {
                $postsController->listerPostsUser($_GET['id']);
            }
            break;
        case 'user-delete':
            if (isset($_GET['id']) && $_SESSION['role'] === 'ROLE_ADMIN') {
                $usersController->destroyUser($_GET['id']);
            }
            break;
        case 'logout':
            $usersController->deconnexionUser();
            exit();
        case 'posts':
            $postsController->listerPosts();
            break;
        case 'create-post':
            $categories = $categoriesController->getAllCategories();
            $postsController->getFormCreatePost($categories);
            break;
        case 'update-post':
            if (isset($_GET['id'])) {
                $post = $postsController->getPostById($_GET['id']);
                $categories = $categoriesController->getAllCategories();
                $postsController->getFormUpdatePost($_GET['id'], $categories);
            }
            break;
        case 'delete-post':
            if (isset($_GET['id']) && ($_SESSION['role'] === 'ROLE_ADMIN' || $_SESSION['id'] === $postsController->getPostById($_GET['id'])['id_user'])) {
                $postsController->destroyPost($_GET['id'], $_SESSION['id']);
            } else {
                echo "Vous n'avez pas les droits nécessaires pour effectuer cette action.";
            }
            break;
        case 'categories':
            $categoriesController->listerCategories();
            break;
        case 'create-categorie':
            if ($_SESSION['role'] === 'ROLE_ADMIN') {
                $categoriesController->getFormCreateCategorie();
            } else {
                echo "Vous n'avez pas les droits nécessaires pour effectuer cette action.";
            }
            break;
        case 'delete-categorie':
            if (isset($_GET['id']) && $_SESSION['role'] === 'ROLE_ADMIN') {
                $categoriesController->destroyCategorie($_GET['id']);
            } else {
                echo "Vous n'avez pas les droits nécessaires pour effectuer cette action.";
            }
            break;
        case 'commentaires-post':
            if (isset($_GET['id'])) {
                $commentairesController->listerCommentaires($_GET['id']);
            }
            break;
        case 'create-commentaire':
            if (isset($_GET['id'])) {
                $commentairesController->getFormCreateCommentaire($_GET['id']);
            }
            break;
        case 'delete-commentaire':
            if (isset($_GET['id'])) {
                $commentaire = $commentairesController->getCommentaireById($_GET['id']);
                if ($_SESSION['role'] === 'ROLE_ADMIN' || $_SESSION['id'] === $commentaire['id_user']) {
                    $commentairesController->destroyCommentaire($_GET['id']);
                } else {
                    echo "Vous n'avez pas les droits nécessaires pour effectuer cette action.";
                }
            }
            break;
        case 'like-post':
            if (isset($_GET['id'])) {
                $usersController->updateUserPostLiked($_SESSION['id'], $_GET['id'], 1);
            }
            break;
        case 'dislike-post':
            if (isset($_GET['id'])) {
                $usersController->updateUserPostLiked($_SESSION['id'], $_GET['id'], -1);
            }
            break;
        case 'enlever-like-post':
            if (isset($_GET['id'])) {
                $usersController->updateUserPostLiked($_SESSION['id'], $_GET['id'], 0);
                header('Location: index.php?page=profil');
                exit();
            }
            break;
        case 'enlever-dislike-post':
            if (isset($_GET['id'])) {
                $usersController->updateUserPostLiked($_SESSION['id'], $_GET['id'], 0);
                header('Location: index.php?page=profil');
                exit();
            }
            break;
        case 'swipe':
            $category_names = $_GET['category_names'] ?? null;
            $categories = $category_names ? explode(',', $category_names) : [];
            $posts = $postsController->getPostsByCategorie($categories);
            $posts_non_likes = array_filter($posts, function ($post) use ($usersController) {
                $like_status = $usersController->getLikedStatus($_SESSION['id'], $post['id']);
                return $like_status === 0 || is_null($like_status);
            });
            if (empty($posts_non_likes)) {
                header('Location: index.php?page=galerie');
                exit();
            }
            require "template-frontoffice/template-swipe.php";
            exit();
        case 'galerie':
            $categories = $categoriesController->getAllCategories();
            $posts = $postsController->getPostsByCategorie($categories);
            require "template-frontoffice/template-galerie.php";
            exit();
        case 'profil':
            $user = $usersController->getUserByIdUser($_SESSION['id']);
            $posts_liked = $usersController->getPostsLikedByUser();
            $posts_disliked = $usersController->getPostsDislikedByUser();
            $posts_added = $postsController->getPostsByUserId($_SESSION['id']);
            $commentaires = $commentairesController->getCommentairesByIdUser($_SESSION['id']);
            $posts_commentaires = $commentairesController->getPostsByCommentaires($_SESSION['id']);
            require "template-frontoffice/template-profil-page.php";
            exit();
        case 'create-post-frontoffice':
            $categories = $categoriesController->getAllCategories();
            require "template-frontoffice/template-create-post-frontoffice.php";
            exit();
        case 'galerie-categorie':
            if (isset($_GET['nom_categorie'])) {
                $posts = $categoriesController->postCategorie($_GET['nom_categorie']);
                require "template-frontoffice/template-one-categorie.php";
                exit();
            }
            break;
        case 'store-favorite-categories':
            $usersController->storeFavoriteCategories();
            break;
        case 'categories-favorites':
            $categories_favorites = $usersController->getFavoritesCategories($_SESSION['id']);
            require "template-frontoffice/template-categories-favorites.php";
            exit();
        case 'mentions-legales':
            require "template-frontoffice/template-mentions-legales.php";
            exit();
        case 'update-post-front':
            if (isset($_GET['id'])) {
                $categories = $categoriesController->getAllCategories();
                $postsController->getFormUpdatePost($_GET['id'], $categories);
                break;
            }
        case 'delete-post-front':
            if (isset($_GET['id'])) {
                $postsController->destroyPost($_GET['id'], $_SESSION['id']);
                header('Location: index.php?page=profil');
                exit();
            }
            break;
        default:
            require "template/template-404.php";
            break;
    }
}
