<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Report;
use App\Form\ReportType;
use Symfony\Component\Mime\Email;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home')]
    public function index(ManagerRegistry $doctrine ,Request $request, MailerInterface $mailer, EntityManagerInterface $entityManager): Response
    {
        $lastes = $doctrine
                ->getRepository(Event::class)
                ->findBy([], ["id" => "DESC"]
                    );
        $report = new Report();

        /* $form = $this->createForm(ReportType::class, $report);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $email = (new Email())
                ->from($data->getEmail())
                ->to('waledo.1997@hotmail.com') // replace with your own email address
                ->subject('New Report')
                ->html($this->renderView(
                        'emails/report.html.twig',
                        [
                            'yes' => $data->isYes(),
                            'no' => $data->isNo(),
                            'name' => $data->getName(),
                            'issueName' => $data->getIssueName(),
                            'issueDescription' => $data->getIssueDescription(),
                        ]
                    ));

            $mailer->send($email);

            $this->addFlash('success', 'Your report has been sent!'); */


            // Create the form using the ReportType form type class
            $form = $this->createForm(ReportType::class, $report);

            // Handle the form submission
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                // Persist the report entity
                $entityManager->persist($report);
                $entityManager->flush();

            $this->addFlash('success', 'The report was added successfully to the report system!');
            return $this->redirectToRoute('app_home');
        }
        return $this->render('home/index.html.twig', [
            'lastes' => $lastes,
            'ReportType' => $form->createView(),
        ]);
    }
    #[Route('/montion', name: 'app_montion')]
    public function montion()
    {
        return $this->render('home/mention.html.twig');

    }
    /**
     * @Route("/process-report", name="process_report", methods={"POST"})
     */
    public function processReport(Request $request, EntityManagerInterface $entityManager , AuthorizationCheckerInterface $authorizationChecker)
    {
        if (!$authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            $this->addFlash('error', 'You need to be connected to add a comment!');
            return $this->redirectToRoute('app_login');
        }
       
        $yes = $request->request->get('Yes');
        $no =  $request->request->get('No');
        $name =$request->request->get('name');
        $email = $request->request->get('email');
        $issueName = $request->request->get('IssueName');
        $issueDescription = $request->request->get('IssueDescription');

         
        $Report = new Report();

        $Report->setYes($yes);
        $Report->setNo($no);
        $Report->setName($name);
        $Report->setEmail($email);
        $Report->setIssueName($issueName);
        $Report->setIssueDescription($issueDescription);
            // Persist the report entity
            $entityManager->persist($Report);
            $entityManager->flush();

            $this->addFlash('success', 'The report was added successfully to the report system!');
            return $this->redirectToRoute('app_home');

    }
}