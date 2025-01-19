<?php
require_once "connexion_bdd.php";
require_once "modele/Users.php";

if (isset($_GET['token'])) {
  $token = $_GET['token'];
  $userModel = new Users($mysqli);

  if ($userModel->verifyEmail($token)) {
    echo "Votre email a été vérifié avec succès.";
  } else {
    echo "Jeton invalide ou expiré.";
  }
} else {
  echo "Aucun jeton fourni.";
}
