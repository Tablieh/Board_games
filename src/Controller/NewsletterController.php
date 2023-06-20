<?php

namespace App\Controller;
use App\Entity\Newsletter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class NewsletterController extends AbstractController
{
    /**
     * @Route("/process-newsletter", name="process_newsletter", methods={"POST"})
     */
    public function processNewsletter(Request $request, EntityManagerInterface $entityManager , AuthorizationCheckerInterface $authorizationChecker)
    {
        if (!$authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            $this->addFlash('error', 'You need to be connected to add a comment!');
            return $this->redirectToRoute('app_login');
        }
        $email = $request->request->get('email');

        $News = new Newsletter();

        $News->setEmail($email);
        

        $entityManager->persist($News);
        $entityManager->flush();

        $this->addFlash('success', 'The email was added successfully to the news letter!');
        return $this->redirectToRoute('app_home');
    }
}