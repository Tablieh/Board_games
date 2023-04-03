<?php

namespace App\Controller;

use App\Entity\Comment;
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
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException as ExceptionAccessDeniedException;

class MakeEventsController extends AbstractController
{
    #[Route('events', name: 'app_join_events')]
    public function index(ManagerRegistry $doctrine,EventRepository $Repo, Request $request , PaginatorInterface $paginator , AuthorizationCheckerInterface $authorizationChecker): Response
    {
         if (!$authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            $this->addFlash('error', 'You need to be logged in to see the events!');
            return $this->redirectToRoute('app_login');
        }
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
                        $request->query->getInt('page', 1),4
                        
                    );
                    //dd($events);
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
    public function add_edit_Event(Event $Event = null, Request $request , ManagerRegistry $doctrine , AuthorizationCheckerInterface $authorizationChecker){
        if (!$authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            $this->addFlash('error', 'You need to be logged in to add a event!');
            return $this->redirectToRoute('app_login');
        }
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
            $this->addFlash('success', 'the Event is well added !');
            return $this->redirectToRoute("app_join_events");

        }
        return $this->render('events/add_edit_Event.html.twig', [
            'EventType' => $form->createView(),
            'editMode'=> $Event->getId() !== null
        ]);
    }
    
    #[Route('/del/{id}', name: 'Event_del')]
    public function del_edit(Event $event , ManagerRegistry $doctrine , AuthorizationCheckerInterface $authorizationChecker)
    {
        if (!$authorizationChecker->isGranted('ROLE_ADMIN')) {
            
            $this->addFlash('error', 'You need to be admin to delete a event!');
            return $this->redirectToRoute('app_login');
        }
        $entityManager = $doctrine->getManager();
        $entityManager->remove($event);
        $entityManager->flush();
        $this->addFlash('success', 'the Event is well deleted!');
        return $this->redirectToRoute('app_join_events');

    }
    #[Route('/event/{id}', name: 'Event_show')]
    public function showEvent(Event $event, BGAHttpClient $bga, Request $request, EntityManagerInterface $entityManager , AuthorizationCheckerInterface $authorizationChecker): Response
    {
         if (!$authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            $this->addFlash('error', 'You need to be logged in to add a comment!');
            return $this->redirectToRoute('app_login');
        }
        $gameId = $event->getIdGame();
        // API
        $resultat = $bga->getGame($gameId);
        // API fin -------------------------

        // Process the comment form
        $comment = new Comment();
        $form = $this->createFormBuilder($comment)
            ->add('content', TextType::class)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setUser($this->getUser());
            $comment->setEvent($event);
            $comment->setCreationDate(new \DateTime());
            $entityManager->persist($comment);
            $entityManager->flush();

            $this->addFlash('success', 'The comment was added successfully!');
            return $this->redirectToRoute('Event_show', ['id' => $event->getId()]);
        }
         
        return $this->render('events/showEvent.html.twig', [
            'results' => $event,
            'gameInfos' => json_decode($resultat, false),
            'comment_form' => $form->createView(),
        ]);
    }
    #[Route('/event/{id}/add-comment', name: 'add_comment')]
    public function addComment(Request $request, EntityManagerInterface $entityManager , AuthorizationCheckerInterface $authorizationChecker)
    {
        // Check if user is authenticated
        if (!$authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            $this->addFlash('error', 'You need to be logged in to add a comment!');
            return $this->redirectToRoute('app_login');
        }
        $content = $request->request->get('content');
        $eventId = $request->request->get('eventId');
        $userId = $this->getUser()->getId();
        $creationDate = new \DateTime();

        // fetch the Event entity using its ID
        $event = $entityManager->getRepository(Event::class)->find($eventId);

        $comment = new Comment();
        $comment->setContent($content);
        $comment->setEvent($event);
        $comment->setUser($entityManager->getReference(User::class, $userId));
        $comment->setCreationDate($creationDate);

        $entityManager->persist($comment);
        $entityManager->flush();

        $this->addFlash('success', 'The comment was added successfully!');
        return $this->redirectToRoute('Event_show', ['id' => $eventId]);
    }

    /* #[Route('/event/{id}', name: 'Event_show')]
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
    } */
    #[Route('/delete/{id}', name: 'Comment_del')]
    public function delComment(Comment $comment, EntityManagerInterface $entityManager , AuthorizationCheckerInterface $authorizationChecker)
    {
        if (!$authorizationChecker->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'You need to be admin to delete a comment!');
            return $this->redirectToRoute('app_login');
        }
        $eventId = $comment->getEvent()->getId();

        $entityManager->remove($comment);
        $entityManager->flush();

        $this->addFlash('success', 'The comment was deleted successfully!');
        return $this->redirectToRoute('Event_show', ['id' => $eventId]);
    }
    
    #[Route('/event/{id}/add-participant/{userId}', name: 'add_participant')]
    public function addParticipant(string $id, string $userId, ManagerRegistry $doctrine , AuthorizationCheckerInterface $authorizationChecker): Response
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
        $this->addFlash('success', 'The place is successfully reserved!');
        return $this->redirectToRoute('Event_show', ['id' => $event->getId()]);
    }
}
