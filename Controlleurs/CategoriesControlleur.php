<?php

require_once __DIR__ . '/../bootsrap.php';
require_once __DIR__ . '/../modele/Categories.php';
require_once __DIR__ . '/../modele/Posts.php';

class CategoriesControlleur
{
    private $twig;
    private $categoriesModel;
    private $postsModel;

    public function __construct($twig, $mysqli)
    {
        $this->twig = $twig;
        $this->categoriesModel = new Categories($mysqli);
        $this->postsModel = new Posts($mysqli);
    }

    public function listerCategories()
    {
        $categories = $this->categoriesModel->getAllCategories();
        echo $this->twig->render('template-liste-categories.twig.html', ['categories' => $categories]);
    }

    public function getFormCreateCategorie()
    {
        echo $this->twig->render('template-create-categories.twig.html');
    }

    public function storeCategorie()
    {
        $nom = htmlspecialchars($_POST['nom'] ?? '');
        $this->categoriesModel->insertCategorie($nom);
    }

    public function destroyCategorie($id)
    {
        $this->categoriesModel->deleteCategorie($id);
    }

    public function getAllCategories()
    {
        return $this->categoriesModel->getAllCategories();
    }

    public function postCategorie($nom_categorie)
    {
        return $this->postsModel->getPostsByCategorie($nom_categorie);
    }
}
