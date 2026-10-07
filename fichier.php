<?php

declare(strict_types=1);

ini_set('display_errors', 'On');
error_reporting(E_ALL);

require_once 'image.php';

if($_FILES['img']['error'] === 0) {

    $image = new Image($_FILES['img']['tmp_name']);
    $image->envoiImage();
    echo $image->afficheImage();

}
