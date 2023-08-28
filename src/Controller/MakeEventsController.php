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
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MakeEventsController extends AbstractController
{
    #[Route('events', name: 'app_join_events')]
    public function index(ManagerRegistry $doctrine,EventRepository $Repo, Request $request , PaginatorInterface $paginator , AuthorizationCheckerInterface $authorizationChecker): Response
    {
        // verfie si la utilisateur est bien connecté et le redirige vers la page de connection
         if (!$authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            $this->addFlash('error', 'You need to be connected to see the events!');
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
            'results' => $result,
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
    public function add_edit_Event(Event $Event = null, Request $request, ManagerRegistry $doctrine, AuthorizationCheckerInterface $authorizationChecker, Security $security, HttpClientInterface $httpClient)
{
    // verfie si la utilisateur est bien connecté et le redirige vers la page de connection
    if (!$authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
        $this->addFlash('error', 'You need to be connected to add a event!');
        return $this->redirectToRoute('app_login');
    }

     // If the user is not an admin and trying to edit an event they didn't create, deny access
    if (!$authorizationChecker->isGranted('ROLE_ADMIN') && $Event && $Event->getCreated() !== $security->getUser()) {
        throw $this->createAccessDeniedException();
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
        //on récupère l'adresse entrée dans le formulaire
        $address = $Event->getAdresse();

        //on envoie une requête à l'API OpenStreetMap pour récupérer les coordonnées de l'adresse
        $response = $httpClient->request('GET', 'https://nominatim.openstreetmap.org/search', [
            'query' => [
                'format' => 'json',
                'q' => $address,
                'limit' => 1,
            ],
        ]);

        //on récupère la réponse de l'API et on extrait les coordonnées
        $data = json_decode($response->getContent(), true);
        if (isset($data[0]['lat']) && isset($data[0]['lon'])) {
            $latitude = $data[0]['lat'];
            $longitude = $data[0]['lon'];

            //on enregistre les coordonnées dans l'objet Event
            $Event->setLat($latitude);
            $Event->setLon($longitude);
        }
        //created par la utilisateur -> user (1) , (2) etc ...
        $Event->setCreated($security->getUser());

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
    public function del_edit(Event $event, ManagerRegistry $doctrine, AuthorizationCheckerInterface $authorizationChecker)
    {
        // Verify if the user is an ADMIN, if not, redirect to the login page
        if (!$authorizationChecker->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'You need to be an admin to delete an event!');
            return $this->redirectToRoute('app_login');
        }

        $entityManager = $doctrine->getManager();

        // Get the comments associated with the event
        $comments = $event->getConcern();

        // Delete each comment
        foreach ($comments as $comment) {
            $entityManager->remove($comment);
        }

        // Flush changes to the database
        $entityManager->flush();

        // Remove the event
        $entityManager->remove($event);
        $entityManager->flush();

        // Success message for the user
        $this->addFlash('success', 'The event and its comments have been successfully deleted!');
        return $this->redirectToRoute('app_join_events');
    }

/*  #[Route('/del/{id}', name: 'Event_del')]
    public function del_edit(Event $event , ManagerRegistry $doctrine , AuthorizationCheckerInterface $authorizationChecker)
    {
        // verfie si la utlisateur est bien ADMIN et si non redirge sur la page de login
        if (!$authorizationChecker->isGranted('ROLE_ADMIN')) {
            
            $this->addFlash('error', 'You need to be admin to delete a event!');
            return $this->redirectToRoute('app_login');
        }
        $entityManager = $doctrine->getManager();
        $entityManager->remove($event);
        $entityManager->flush();
        // msg de sucess pour la utlisateur 
        $this->addFlash('success', 'the Event is well deleted!');
        return $this->redirectToRoute('app_join_events');

    }  */

    #[Route('/event/{id}', name: 'Event_show')]
    public function showEvent(Event $event, BGAHttpClient $bga, Request $request, EntityManagerInterface $entityManager , AuthorizationCheckerInterface $authorizationChecker): Response
    {
        // verfie si la utlisateur est bien connecté et redirgé vers la page de connection
         if (!$authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            $this->addFlash('error', 'You need to be connected to add a comment!');
            return $this->redirectToRoute('app_login');
        }
        $gameId = $event->getIdGame();
        // API
        $resultat = $bga->getGame($gameId);
        // API fin -------------------------

        // Process the comment form
        $comment = new Comment();
        // creation de la fourmulaire 
        $form = $this->createFormBuilder($comment)
            ->add('content', TextType::class)
            ->getForm();

        $form->handleRequest($request);
        // verfie si la form est bien sumite et si est valide
        // assosier la utlisateur et la event et la comment ensemble avec la date et heure de click puis persist et flush
        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setUser($this->getUser());
            $comment->setEvent($event);
            $comment->setCreationDate(new \DateTime());
            $entityManager->persist($comment);
            $entityManager->flush();

            $this->addFlash('success', 'The comment was added successfully!');
            return $this->redirectToRoute('Event_show', ['id' => $event->getId()]);
        }

        //dd(json_decode($resultat, true));
         
        return $this->render('events/showEvent.html.twig', [
            'results' => $event,
            'gameInfos' => json_decode($resultat, false),
            'comment_form' => $form->createView(),
        ]);
    }

    #[Route('/event/{id}/add-comment', name: 'add_comment')]
    public function addComment(Request $request, EntityManagerInterface $entityManager , AuthorizationCheckerInterface $authorizationChecker)
    {
        // Check if user is authenticated et si non redirect vers login
        if (!$authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            $this->addFlash('error', 'You need to be connected to add a comment!');
            return $this->redirectToRoute('app_login');
        }
        // prends la content et event id et user Id et creation de la date de click
        $content = $request->request->get('content');
        $eventId = $request->request->get('eventId');
        $userId = $this->getUser()->getId();
        $creationDate = new \DateTime();

        // fetch the Event entity using its ID
        $event = $entityManager->getRepository(Event::class)->find($eventId);

        // ajouter une nouvelle comment et prends ca content et le setter dans la comment
        // assosiat la comment et la event ensemble 
        // avec le refierencé avec USER ID et la creation de date de click sur ajoute ce comment
        // puis persist et flush 
        $comment = new Comment();
        $comment->setContent($content);
        $comment->setEvent($event);
        $comment->setUser($entityManager->getReference(User::class, $userId));
        $comment->setCreationDate($creationDate);

        $entityManager->persist($comment);
        $entityManager->flush();

        // ajouter une msg de sucess pour la ajout de comment 
        // et la redirect vers la page de event de ce comment
        $this->addFlash('success', 'The comment was added successfully!');
        return $this->redirectToRoute('Event_show', ['id' => $eventId]);
    }

    #[Route('/delete/{id}', name: 'Comment_del')]
    public function delComment(Comment $comment, EntityManagerInterface $entityManager , AuthorizationCheckerInterface $authorizationChecker)
    {
        // verfie si la utlisateur est bien ADMIN et si non redirge sur la page de login
        if (!$authorizationChecker->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'You need to be admin to delete a comment!');
            return $this->redirectToRoute('app_login');
        }
        // prends la id du EVENT 
        $eventId = $comment->getEvent()->getId();
        // suprimme la comment puis flush
        $entityManager->remove($comment);
        $entityManager->flush();

        // msg de succes de suprimmer de comment et redirect vers la page de event de ce comment
        $this->addFlash('success', 'The comment was deleted successfully!');
        return $this->redirectToRoute('Event_show', ['id' => $eventId]);
    }
    

   /*  #[Route('/event/{id}/add-participant/{userId}', name: 'add_participant')]
public function addParticipant(string $id, string $userId, ManagerRegistry $doctrine, AuthorizationCheckerInterface $authorizationChecker ,Security $security): Response
{

    $eventRepository = $doctrine->getRepository(Event::class);
    $userRepository = $doctrine->getRepository(User::class);

    $event = $eventRepository->find($id);
    $user = $userRepository->find($userId);
    // verfie si la utilisateur est bien connecté et le redirige vers la page de connection
    if (!$authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
        $this->addFlash('error', 'Connected as same user required or register required !');
        return $this->redirectToRoute('app_login');
    }
    //verfie si la utilisateur est bien la meme avant de passer pour addition la participation des events
    // et sinon on envoie une msg d'error qui dit Acess Denied et dans ce case rederige veres la page login pour afficher ce message
    if ($security->getUser() != $user){
        $this->addFlash('error', 'Acess Denied !');
        return $this->redirectToRoute('app_login');
    }
    // verfie si la event ou bien la utilisateur n'exist pas 
    if (!$event || !$user) {
        throw $this->createNotFoundException();
    }

    // Check if the maximum number of participants has been reached
    if (count($event->getParticipant()) >= $event->getPlaces()) {
        $this->addFlash('success', 'Sorry, the event is already full and you cannot join!');
        return $this->redirectToRoute('Event_show', ['id' => $event->getId()]);
    }
    // ajoute la participant de event et l'assosier par la event puis persist
    $event->addParticipant($user);

    $entityManager = $doctrine->getManager();
    $entityManager->persist($event);
    $entityManager->flush();

    $this->addFlash('success', 'The place is successfully reserved!');
    return $this->redirectToRoute('Event_show', ['id' => $event->getId()]);
} */

#[Route('/event/{id}/add-participant', name: 'add_participant')]
public function addParticipant(string $id, ManagerRegistry $doctrine, AuthorizationCheckerInterface $authorizationChecker, Security $security): Response
{
    $eventRepository = $doctrine->getRepository(Event::class);

    $event = $eventRepository->find($id);
    $user = $security->getUser();

    // Verify if the user is logged in and redirect to the login page if not
    if (!$authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
        $this->addFlash('error', 'Connected as same user required or register required!');
        return $this->redirectToRoute('app_login');
    }

    // Verify if the user is the same before proceeding to add the participant to the event
    // Otherwise, display an "Access Denied" error message and redirect to the login page
    if ($event->getParticipant()->contains($user)) {
        $this->addFlash('error', 'This User Is Already participent!');
        return $this->redirectToRoute('Event_show', ['id' => $event->getId()]);
    }

    // Verify if the event or the user does not exist
    if (!$event || !$user) {
        throw $this->createNotFoundException();
    }

    // Check if the maximum number of participants has been reached
    if (count($event->getParticipant()) >= $event->getPlaces()) {
        $this->addFlash('success', 'Sorry, the event is already full and you cannot join!');
        return $this->redirectToRoute('Event_show', ['id' => $event->getId()]);
    }

    // Add the participant to the event and persist the changes
    $event->addParticipant($user);

    $entityManager = $doctrine->getManager();
    $entityManager->persist($event);
    $entityManager->flush();

    $this->addFlash('success', 'The place is successfully reserved!');
    return $this->redirectToRoute('Event_show', ['id' => $event->getId()]);
}

}
