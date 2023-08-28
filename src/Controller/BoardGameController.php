<?php

namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\HttpClient\BGAHttpClient;

class BoardGameController extends AbstractController
{
    #[Route('/board/game', name: 'app_board_game')]
    public function index(): Response
    {
        return $this->render('board_game/index.html.twig', [
            'controller_name' => 'BoardGameController',
        ]);
    }
    #[Route('/games', name: 'app_games', methods: ['POST'])]
    public function displayGames(BGAHttpClient $bgg, Request $request) {
        $search = $request->request->get('searchValue');
        return new Response($bgg->getGames($search));
    }

    #[Route('/game', name: 'app_game', methods: ['POST'])]
    public function displayGame(BGAHttpClient $bgg, Request $request) {
        $search = $request->request->get('gameId');
        return new Response($bgg->getGame($search));
    }
    
}
