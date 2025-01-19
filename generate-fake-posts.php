<?php


require_once __DIR__ . '/modele/Posts.php';
require_once __DIR__ . '/modele/Users.php';
require_once __DIR__ . '/Controlleurs/PostsControlleur.php';
require_once __DIR__ . '/Controlleurs/CategoriesControlleur.php';
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/connexion_bdd.php';
require_once __DIR__ . '/bootstrap.php';

use Faker\Factory;

$faker = Factory::create('fr_FR');

$postsModel = new Posts($mysqli);
$usersModel = new Users($mysqli);
$postsController = new PostsControlleur($twig, $mysqli);
$categoriesController = new CategoriesControlleur($twig, $mysqli);
$users = $usersModel->getAllUsers();
if (empty($users)) {
  die("Aucun utilisateur trouvé dans la base de données.");
}
$categories = $categoriesController->getAllCategories();
$nombreDePosts = 50;

for ($i = 0; $i < $nombreDePosts; $i++) {
  $titre = $faker->sentence(2, true);
  $description = $faker->paragraph(3, true);
  $user = $faker->randomElement($users);
  $nom_categorie = $faker->randomElement($categories);
  if (is_array($nom_categorie)) {
    $nom_categorie = $nom_categorie['nom'];
  }
  $imageUrl = "https://picsum.photos/800/600";
  $tmpImage = sys_get_temp_dir() . '/fake_post_' . uniqid() . '.jpg';
  file_put_contents($tmpImage, file_get_contents($imageUrl));

  $fakeFile = [
    'tmp_name' => $tmpImage,
    'name' => 'fake_image_' . $i . '.jpg',
    'type' => 'image/jpeg',
    'error' => UPLOAD_ERR_OK,
    'size' => filesize($tmpImage)
  ];

  $imagePath = $postsController->processusImages($fakeFile, $user['id']);
  if ($imagePath) {
    if ($postsModel->insertPost($titre, $description, $imagePath, $user['id'], $nom_categorie)) {
      echo "Post $i inséré avec succès (Image: $imagePath)\n";
    } else {
      echo "Erreur lors de l'insertion du post $i\n";
    }
  } else {
    echo "Erreur lors du traitement de l'image pour le post $i\n";
  }
  unlink($tmpImage);
  usleep(500000);
}

echo "Génération terminée !";
