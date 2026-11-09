<?php

namespace App\Controller;

use App\Entity\Event;
use App\Form\EventType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class EventController extends AbstractController
{
   
    private function handleImageUpload(FormInterface $form, Event $event): void
    {
        $imageFile = $form->get('image')->getData();
        if ($imageFile) {
            $fileName = uniqid() . '.' . $imageFile->guessExtension();
            $imageFile->move($this->getParameter('kernel.project_dir') . '/public/uploads', $fileName);
            $event->setImage($fileName);
        }
    }

    #[Route('/evenement/ajouter', name: 'app_event_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $event = new Event();
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImageUpload($form, $event);

            if ($this->getUser()) {
                $event->setAuthor($this->getUser());
            }

            
            $event->setValidated(false);

            $entityManager->persist($event);
            $entityManager->flush();

            $this->addFlash('success', 'Votre événement a bien été ajouté. Il sera visible après validation.');

            return $this->redirectToRoute('app_home');
        }

        return $this->render('event/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/evenement/{id}/modifier', name: 'app_event_edit', methods: ['GET', 'POST'])]
    public function edit(Event $event, Request $request, EntityManagerInterface $entityManager): Response
    {
        
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

       
        if ($event->getAuthor() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImageUpload($form, $event);
            $entityManager->flush();

            $this->addFlash('success', 'L\'événement a été modifié.');

            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
        }

        return $this->render('event/edit.html.twig', [
            'form' => $form,
            'event' => $event,
        ]);
    }

    #[Route('/evenement/{id}', name: 'app_event_show', requirements: ['id' => '\d+'])]
    public function show(Event $event): Response
    {
        return $this->render('event/show.html.twig', [
            'event' => $event,
        ]);
    }

    #[Route('/evenement/{id}/inscription', name: 'app_event_register', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function register(Event $event, EntityManagerInterface $entityManager): Response
    {
        if ($event->getRemainingPlaces() <= 0) {
            $this->addFlash('error', 'Désolé, cet événement est complet.');
        } else {
            $event->addParticipant($this->getUser());
            $entityManager->flush();

            $this->addFlash('success', 'Vous êtes inscrit à cet événement.');
        }

        return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
    }

    #[Route('/evenement/{id}/desinscription', name: 'app_event_unregister', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function unregister(Request $request, Event $event, EntityManagerInterface $entityManager): Response
    {
        $event->removeParticipant($this->getUser());
        $entityManager->flush();

        $this->addFlash('success', 'Vous êtes désinscrit de cet événement.');

        $referer = $request->headers->get('referer');
        return $this->redirect($referer ?? $this->generateUrl('app_my_events'));
    }

    #[Route('/mes-inscriptions', name: 'app_my_events')]
    #[IsGranted('ROLE_USER')]
    public function myEvents(): Response
    {
        $events = $this->getUser()->getEvents();

        return $this->render('event/mine.html.twig', [
            'events' => $events,
        ]);
    }
    #[Route('/validate/{id}/refuse', name: 'app_event_refuse', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function refuse(Event $event, EntityManagerInterface $entityManager): Response
    {
        $title = $event->getTitle();
        
        $entityManager->remove($event);
        $entityManager->flush();

        $this->addFlash('success', 'L\'événement « ' . $title . ' » a été refusé.');

        return $this->redirectToRoute('app_validate');
    }
}