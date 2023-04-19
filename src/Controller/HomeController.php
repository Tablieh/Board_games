<?php

namespace App\Controller;

use App\Entity\Event;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Report;
use App\Form\ReportType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home')]
    public function index(ManagerRegistry $doctrine ,Request $request, MailerInterface $mailer): Response
    {
        $lastes = $doctrine
                ->getRepository(Event::class)
                ->findBy([], ["id" => "DESC"]
                    );
        $report = new Report();
        $form = $this->createForm(ReportType::class, $report);
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

            $this->addFlash('success', 'Your report has been sent!');
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
}