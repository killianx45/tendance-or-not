<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


require_once __DIR__ . '/../bootsrap.php';
require_once __DIR__ . '/../modele/Users.php';
require_once __DIR__ . '/../modele/Categories.php';


class UsersControlleur
{
    private $twig;
    private $usersModel;
    private $categoriesModel;

    public function __construct($twig, $mysqli)
    {
        $this->twig = $twig;
        $this->usersModel = new Users($mysqli);
        $this->categoriesModel = new Categories($mysqli);
    }

    public function listerUsers()
    {
        $users = $this->usersModel->getAllUsers();
        echo $this->twig->render('template-liste-users.twig.html', ['users' => $users]);
    }

    public function getUserByIdUser($id)
    {
        return $this->usersModel->getUserById($id);
    }

    public function getFormCreateUser()
    {
        echo $this->twig->render('template-create-user.twig.html');
    }

    public function storeUser()
    {
        $nom = htmlspecialchars($_POST['nom']);
        $pseudo = htmlspecialchars($_POST['pseudo']);

        if (strlen($nom) < 3 || strlen($nom) > 50) {
            return;
        }

        if (strlen($pseudo) < 3 || strlen($pseudo) > 50) {
            return;
        }
        $email = htmlspecialchars($_POST['email']);
        $password = password_hash(htmlspecialchars($_POST['password']), PASSWORD_DEFAULT);
        $age = (int)$_POST['age'];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }
        $existingUser = $this->usersModel->getUserByEmail($email);
        if ($existingUser) {
            return;
        }
        $userId = $this->usersModel->insertUser($nom, $pseudo, $email, $password, $age);
        if ($userId) {
            $this->sendVerificationEmail($userId, $email);
        }
    }


    public function connexionUser()
    {
        $email = htmlspecialchars($_POST['email']);
        $password = htmlspecialchars($_POST['password']);
        $user = $this->usersModel->getUserByEmail($email);

        if ($user && password_verify($password, $user['password'])) {
            if (!$user['is_verified']) {
                echo "Veuillez vérifier votre email avant de vous connecter.";
                return;
            }
            $_SESSION['role'] = $user['role'];
            $_SESSION['id'] = $user['id'];
            $_SESSION['nom'] = $user['nom'];
            $_SESSION['pseudo'] = $user['pseudo'];
            $_SESSION['is_connected'] = true;
            header('Location: /sae_301/');
        } else {
            echo "Erreur lors de la connexion.";
        }
    }

    public function deconnexionUser()
    {
        session_destroy();
        header('Location: connexion_user.php');
        exit();
    }

    public function destroyUser($id)
    {
        if ($_SESSION['role'] === 'ROLE_ADMIN') {
            $this->usersModel->deleteUser($id);
            header('Location: /sae_301/');
        } else {
            echo "Accès refusé : vous n'avez pas les droits nécessaires pour effectuer cette action.";
        }
    }

    public function verifierConnexion()
    {
        session_start();
        if (isset($_SESSION['is_connected']) && $_SESSION['is_connected'] === true) {
            header('Location: /sae_301/index.php');
            exit();
        }
    }

    public function updateUserPostLiked($user_id, $post_id, $like_value)
    {
        return $this->usersModel->updateUserPostLike($user_id, $post_id, $like_value);
    }

    public function getPostsLikedByUser()
    {
        return $this->usersModel->getPostsLikeByUser($_SESSION['id']);
    }

    public function getPostsDislikedByUser()
    {
        return $this->usersModel->getPostsDislikeByUser($_SESSION['id']);
    }

    public function getLikedStatus($user_id, $post_id)
    {
        return $this->usersModel->getLikeStatus($user_id, $post_id);
    }

    public function addFavoriteCategories($id_user, $id_categorie)
    {
        return $this->usersModel->addFavoriteCategorie($id_user, $id_categorie);
    }

    public function storeFavoriteCategories()
    {
        $id_user = $_SESSION['id'];
        $categories = json_decode($_POST['categories'], true);
        foreach ($categories as $nom_categorie) {
            $categorie = $this->categoriesModel->getCategoryByName($nom_categorie);
            if ($categorie) {
                $this->addFavoriteCategories($id_user, $categorie['id']);
            }
        }
        $_SESSION['selected_categories'] = $categories;
        header('Location: index.php?page=swipe');
        exit();
    }


    public function getFavoritesCategories($id_user)
    {
        return $this->usersModel->getFavoriteCategories($id_user);
    }

    public function sendVerificationEmail($userId, $email)
    {
        $token = bin2hex(random_bytes(16));
        $this->usersModel->storeVerificationToken($userId, $token);

        $verificationUrl = "http://localhost:8888/sae_301/verifier_email.php?token=$token";

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV["MAIL_USER"];
            $mail->Password = $_ENV["MAIL_PASSWORD"];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->setFrom($_ENV["MAIL_USER"], 'Tendance or Not');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Vérification de votre adresse email';
            $mail->Body = "<p>Bonjour,</p><p>Veuillez cliquer sur le lien suivant pour vérifier votre email :</p><a href=\"$verificationUrl\">Vérifier mon email</a><p>Ceci est un email automatique, veuillez ne pas répondre.</p>";

            $mail->send();
            echo "Email de vérification envoyé.";
        } catch (Exception $e) {
            echo "Erreur lors de l'envoi de l'email : {$mail->ErrorInfo}";
        }
    }
}
