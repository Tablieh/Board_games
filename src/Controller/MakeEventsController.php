<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Event;
use App\Form\EventType;
use App\HttpClient\BGAHttpClient;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class MakeEventsController extends AbstractController
{
    #[Route('events', name: 'app_join_events')]
    public function index(ManagerRegistry $doctrine,EventRepository $Repo, Request $request , PaginatorInterface $paginator): Response
    {
        /* $events = $doctrine->getManager()
                ->getRepository(Event::class)
                ->findAll(); */
        $result = $doctrine
                ->getRepository(Event::class)
                ->findBy([], ["id" => "DESC"]
                    );
        $events = $paginator->paginate(
            //$Repo->pagintationQuery(),
            $result,
            $request->query->getInt('page', 1),10

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
    
    #[Route('/del/{id}', name: 'Event_del')]
    public function del_edit(Event $event , ManagerRegistry $doctrine)
    {

        $entityManager = $doctrine->getManager();
        $entityManager->remove($event);
        $entityManager->flush();
        $this->addFlash('error', 'the comment is well deleted!');
        return $this->redirectToRoute('app_join_events');

    }

    #[Route('/event/{id}', name: 'Event_show')]
    public function showEvent(Event $event ,BGAHttpClient $bga): Response {
        $gameId = $event->getIdGame();
        // API
        $resultat = $bga->getGame($gameId);
        //$gameInfos = $resultat;
        //dd($gameInfos);
        return $this->render('events/showEvent.html.twig', [
            'results' => $event,
            'gameInfos' => json_decode($resultat, false)
        ]);
    }
        #[Route('/event/{id}/add-participant/{userId}', name: 'add_participant')]
    public function addParticipant(string $id, string $userId, ManagerRegistry $doctrine): Response
    {
        $eventRepository = $doctrine->getRepository(Event::class);
        $userRepository = $doctrine->getRepository(User::class);

        $event = $eventRepository->find($id);
        $user = $userRepository->find($userId);

        if (!$event || !$user) {
            throw $this->createNotFoundException();
        }

        $event->addParticipant($user);

        $entityManager = $doctrine->getManager();
        $entityManager->persist($event);
        $entityManager->flush();

        return $this->redirectToRoute('Event_show', ['id' => $event->getId()]);
    }

}
