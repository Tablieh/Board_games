<?php

namespace App\Controller;

use App\Entity\Event;
use App\Form\EventType;
use App\HttpClient\BGAHttpClient;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MakeEventsController extends AbstractController
{
    #[Route('events', name: 'app_join_events')]
    public function index(ManagerRegistry $doctrine): Response
    {
        $events = $doctrine->getManager()
                ->getRepository(Event::class)
                ->findAll();
        $result = $doctrine
                ->getRepository(Event::class)
                ->findBy([], ["id" => "ASC"]
                    );
        return $this->render('events/index.html.twig', [
            'events' => $events,
            'results' => $result
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

    #[Route('/addEvent', name: 'Event_add')]
    #[Route('/{id}/editEvent', name: 'Event_edit')]
    public function add_edit_Event(Event $Event = null, Request $request , ManagerRegistry $doctrine){
        // si le Event n'existe pas, on instancie un nouveau Event(on est dans le cas d'un ajout) 
        if(!$Event){
            $Event = new Event();
        }
        //il faut créer un Event au préalable (php bin/console make:form)
        $form = $this->createForm(EventType::class, $Event );

        $form->handleRequest($request);
        // si on soumet le formulaire et que le form est validé
        if($form->isSubmitted() && $form->isValid()){
           /*  $Event->setIdGame($request->request->get('event')['id_game']); */
            //on récuprère les données du formulaire
            $Event = $form->getData();
            //on ajoute le nouveau Event
            $entityManager = $doctrine->getManager();
            $entityManager->persist($Event);
            $entityManager->flush();
            //on redirige vers la liste des Event (Marque_list etant le nom de la route)
            $this->addFlash('success', 'the comment is well added !');
            return $this->redirectToRoute("app_join_events");

        }
        return $this->render('events/add_edit_Event.html.twig', [
            'EventType' => $form->createView(),
            'editMode'=> $Event->getId() !== null
        ]);
    }
    /**
     * @Route("/del/{id}", name="Event_del")
     */
    public function del_edit(Event $event , ManagerRegistry $doctrine)
    {

        $entityManager = $doctrine->getManager();
        $entityManager->remove($event);
        $entityManager->flush();
        $this->addFlash('error', 'the comment is well deleted!');
        return $this->redirectToRoute('app_join_events');

    }

    /**
     * @Route("/event/{id}", name="Event_show", methods="GET")
     */
    
    public function showEvent(Event $results): Response {
        //dd($results);
        return $this->render('events/showEvent.html.twig', [
            'results' => $results
        ]);
    }
}
