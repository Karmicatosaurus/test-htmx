<?php

declare(strict_types=1);

require_once 'image.php';

if($_FILES['img']['error'] === 0) {

    $image = new Image($_FILES['img']['tmp_name']);
    $image->envoiImage();
    echo $image->afficheImage();

}
