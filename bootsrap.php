<?php
require_once 'vendor/autoload.php';
require_once 'connexion_bdd.php';

$loader = new \Twig\Loader\FilesystemLoader([
  'template',
]);

$twig = new \Twig\Environment($loader, [
  'cache' => false,
  'debug' => true
]);
