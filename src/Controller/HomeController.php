<?php

namespace App\Controller;

use App\Entity\Event;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home')]
    public function index(ManagerRegistry $doctrine): Response
    {
        $lastes = $doctrine
                ->getRepository(Event::class)
                ->findBy([], ["id" => "DESC"]
                    );
        return $this->render('home/index.html.twig', [
            'lastes' => $lastes
        ]);
    }
    #[Route('/montion', name: 'app_montion')]
    public function montion()
    {
        return $this->render('home/mention.html.twig');

    }
}