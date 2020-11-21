<?php

namespace App\Controller;

use App\Model\AdminBicycleManager;
use App\Model\CategoryManager;
use App\Model\BicycleManager;
use App\Model\ReservationManager;

class AdminBicycleController extends AbstractController
{
    public const MAX_FILE_SIZE = 1000000;
    public const AUTHORIZED_MIMES = ['image/jpeg', 'image/png'];

    /**
     * Display bicycle creation page
     *
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */

    public function index()
    {
        $error = "";
        $adminBikeManager = new BicycleManager();
        $bikes = $adminBikeManager->selectAllWithCategories();
        $error = $this->remove();
        return $this->twig->render('Admin/bikes.html.twig', ['error' => $error, 'bikes' => $bikes]);
    }

    public function remove()
    {
        $error = "";
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = array_map('trim', $_POST);
            $reservationManager = new ReservationManager();
            if ($reservationManager->isReservedBike((int)$data['id'])) {
                $error = "Ce vélo est réservé, il est donc impossible de le supprimer !";
            } else {
                $bicycleManager = new BicycleManager();
                $bicycleManager->delete((int)$data['id']);
                header('Location:/AdminBicycle/index');
            }
            return $error;
        }
    }


    public function addBike()
    {
        $categoryManager = new CategoryManager();
        $categories = $categoryManager->selectAll();
        $bike = [];
        if ($_SERVER['REQUEST_METHOD'] === "POST") {
            $bike = array_map('trim', $_POST);
            $errors = $this->validateBike($bike, $_FILES['image']);

            if (empty($errors)) {
                $category = $categoryManager->selectOneById((int)$bike['category_id']);
                $uploadDirectory = 'assets/images/' . $category['name'];
                $filename = $_FILES['image']['name'];
                move_uploaded_file($_FILES['image']['tmp_name'], $uploadDirectory . '/' . $filename);
                $bike['image'] = $filename;

                $adminBicycleManager = new AdminBicycleManager();
                $adminBicycleManager->insert($bike);
                header("location:/AdminBicycle/index");
            }
        }
        return $this->twig->render('Admin/add-bike.html.twig', ['errors' => $errors ?? [],
            'bike' => $bike, 'categories' => $categories
        ]);
    }


    public function editBike(int $id)
    {
        $categoryManager = new CategoryManager();
        $categories = $categoryManager->selectAll();

        $adminBicycleManager = new AdminBicycleManager();
        $editBike = $adminBicycleManager->selectOneById($id);
        if ($_SERVER['REQUEST_METHOD'] === "POST") {
            $bike = array_map('trim', $_POST);
            $errors = $this->validateBike($bike);

            if (empty($errors)) {
                $adminBicycleManager = new AdminBicycleManager();
                $adminBicycleManager->update($bike, $id);
                header("location:/AdminBicycle/index");
            }
        }

        return $this->twig->render('Admin/editor-bike.html.twig', ['errors' => $errors ?? [],
            'bike' => $editBike, 'categories' => $categories ]);
    }





    /**
     * @SuppressWarnings(PHPMD)
     * @param array $bike
     * @return array
     */

    private function validateBike(array $bike, array $file)
    {
        $errors = [];
        if (empty($bike['name'])) {
            $errors[] = 'Le nom du vélo est obligatoire.';
        }
        $length = 100;
        if (strlen($bike['name']) > $length) {
            $errors[] = 'Le nom du vélo ne doit pas dépasser ' . $length . ' caractères.';
        }

        if (empty($bike['weight']) || $bike['weight'] < 0) {
            $errors[] = 'Le poids du vélo est obligatoire et doit être positif.';
        }

        if (empty($bike['stock']) || $bike['stock'] < 0) {
            $errors[] = 'le nombre de vélo ne peut être inférieur à 0';
        }

        if (empty($bike['category_id'])) {
            $errors[] = 'La selection de la catégorie est obligatoire.';
        }

        if ($file['size'] > self::MAX_FILE_SIZE) {
            $errors[] = 'Le fichier ne doit pas excéder ' . self::MAX_FILE_SIZE / 1000000 . ' Mo';
        }
        if (!empty($file['tmp_name']) && !in_array(mime_content_type($file['tmp_name']), self::AUTHORIZED_MIMES)) {
            $errors[] = 'Ce type de fichier n\'est pas valide';
        }
        return $errors;
    }
}
