<?php
namespace App\Controllers;

class HomeController extends Controller
{

    public function __construct($container)
    {
        parent::__construct($container);
    }

    public function index($request, $response, $args)
    {
        $this->logger->info("Slim-Skeleton '/' route");

        return $this->view->render($response, 'home.twig', [
            'name' => isset($args['name']) ? utf8_encode($args['name']) : ''
        ]);
    }
}
