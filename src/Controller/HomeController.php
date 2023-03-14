<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\HttpClient\BGAHttpClient;

class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }
    #[Route('/games' , name: 'app_games', methods: ['POST'])]
    public function displayGames(BGAHttpClient $bga , Request $request){
        $search = $request->request->get('searchValue');
        return new Response($bga->getGames($search));
    }
    #[Route('/game' , name: 'app_game', methods: ['POST'])]
    public function displayGame(BGAHttpClient $bga , Request $request){
        $search = $request->request->get('gameId');
        return new Response($bga->getGame($search));
    }
}