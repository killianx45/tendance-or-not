<?php
require_once "config.php";
require_once "connexion_bdd.php";
require_once "Controlleurs/UsersControlleur.php";

$usersController = new UsersControlleur($twig, $mysqli);
$usersController->verifierConnexion();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $usersController->connexionUser();
}
?>
<!DOCTYPE html>
<html data-theme="sunset" lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tendance or Not - Connexion</title>
  <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.14/dist/full.min.css" rel="stylesheet" type="text/css" />
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body>
  <div class="erreur">
    <p class="text-sm text-center font-bold"></p>
  </div>
  <div class="flex justify-center items-center">
    <form action="" method="POST" class="flex flex-col gap-4 w-1/2">
      <p class="text-2xl text-center font-bold">Connectez-vous</p>
      <label for="email" class="input input-bordered flex items-center gap-2">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 16 16"
          fill="currentColor"
          class="h-4 w-4 opacity-70">
          <path
            d="M2.5 3A1.5 1.5 0 0 0 1 4.5v.793c.026.009.051.02.076.032L7.674 8.51c.206.1.446.1.652 0l6.598-3.185A.755.755 0 0 1 15 5.293V4.5A1.5 1.5 0 0 0 13.5 3h-11Z" />
          <path
            d="M15 6.954 8.978 9.86a2.25 2.25 0 0 1-1.956 0L1 6.954V11.5A1.5 1.5 0 0 0 2.5 13h11a1.5 1.5 0 0 0 1.5-1.5V6.954Z" />
        </svg>
        <input class="grow" placeholder="your@email.com" type="email" name="email" id="email" required />
      </label>
      <label for="password" class="input input-bordered flex items-center gap-2">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 16 16"
          fill="currentColor"
          class="h-4 w-4 opacity-70">
          <path
            fill-rule="evenodd"
            d="M14 6a4 4 0 0 1-4.899 3.899l-1.955 1.955a.5.5 0 0 1-.353.146H5v1.5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5v-2.293a.5.5 0 0 1 .146-.353l3.955-3.955A4 4 0 1 1 14 6Zm-4-2a.75.75 0 0 0 0 1.5.5.5 0 0 1 .5.5.75.75 0 0 0 1.5 0 2 2 0 0 0-2-2Z"
            clip-rule="evenodd" />
        </svg>
        <input type="password" name="password" id="password" required class="grow" placeholder="********" />
      </label>
      <button class="btn btn-primary" type="submit">Se connecter</button>
    </form>
  </div>
  <div class="flex justify-center items-center mt-5">
    <a href="index.php?page=inscription_user" class="btn btn-info">S'inscrire</a>
  </div>
</body>

</html>