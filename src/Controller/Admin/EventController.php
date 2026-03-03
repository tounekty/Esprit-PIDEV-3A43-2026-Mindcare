<?php

namespace App\Controller\Admin;

use App\Entity\Event;
use App\Form\EventType;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\EventReservationRepository;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[Route('/admin/events', name: 'admin_events_')]
class EventController extends AbstractController
{
    private string $eventImageDirectory = 'uploads/events';

    private function handleEventImageUpload(?UploadedFile $file, ?string $oldImage = null): ?string
    {
        if (!$file) {
            return $oldImage;
        }

        // Delete old image if exists
        if ($oldImage) {
            $oldPath = $this->getParameter('kernel.project_dir') . '/public/' . $oldImage;
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }

        // Get file extension - handle cases where guessExtension returns null
        $extension = $file->guessExtension();
        if (!$extension) {
            // Fallback: extract extension from original filename
            $originalName = $file->getClientOriginalName();
            $extension = pathinfo($originalName, PATHINFO_EXTENSION);
            
            // If still no extension, use a default based on mime type
            if (!$extension) {
                $mimeType = $file->getMimeType();
                if (strpos($mimeType, 'image/jpeg') !== false || strpos($mimeType, 'image/jpg') !== false) {
                    $extension = 'jpg';
                } elseif (strpos($mimeType, 'image/png') !== false) {
                    $extension = 'png';
                } elseif (strpos($mimeType, 'image/webp') !== false) {
                    $extension = 'webp';
                } else {
                    $extension = 'jpg'; // default
                }
            }
        }

        // Generate new filename with proper extension
        $newFilename = uniqid() . '.' . strtolower($extension);

        // Create directory if not exists
        $uploadDir = $this->getParameter('kernel.project_dir') . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'events';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Move the file
        $file->move($uploadDir, $newFilename);

        return 'uploads/events/' . $newFilename;
    }
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(EventRepository $eventRepository, EventReservationRepository $reservationRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PSYCHOLOGUE');

        $allEvents = $eventRepository->findBy([], ['dateEvent' => 'DESC']);
        
        // Calculate statistics
        $totalEvents = count($allEvents);
        $totalReservations = 0;
        $totalCapacity = 0;
        $eventsByCategory = [];
        $upcomingEvents = [];
        
        $now = new \DateTime();
        
        foreach ($allEvents as $event) {
            $totalCapacity += $event->getCapacite();
            $activeCount = $reservationRepository->countActiveByEvent($event);
            $totalReservations += $activeCount;
            
            // Group by category
            $category = $event->getCategorie() ?? 'Autre';
            if (!isset($eventsByCategory[$category])) {
                $eventsByCategory[$category] = 0;
            }
            $eventsByCategory[$category]++;
            
            // Get upcoming events
            if ($event->getDateEvent() > $now) {
                $upcomingEvents[] = $event;
            }
        }
        
        usort($upcomingEvents, function($a, $b) {
            return $a->getDateEvent() <=> $b->getDateEvent();
        });
        $upcomingEvents = array_slice($upcomingEvents, 0, 5);

        return $this->render('admin/gestion_events/index.html.twig', [
            'events' => $allEvents,
            'totalEvents' => $totalEvents,
            'totalReservations' => $totalReservations,
            'totalCapacity' => $totalCapacity,
            'occupancyRate' => $totalCapacity > 0 ? round(($totalReservations / $totalCapacity) * 100, 1) : 0,
            'eventsByCategory' => $eventsByCategory,
            'upcomingEvents' => $upcomingEvents,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PSYCHOLOGUE');

        $event = new Event();
        $event->setUser($this->getUser());
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle image upload
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $imagePath = $this->handleEventImageUpload($imageFile);
                $event->setImage($imagePath);
            }

            $em->persist($event);
            $em->flush();

            $this->addFlash('success', 'Evenement cree avec succes !');

            return $this->redirectToRoute('admin_events_index');
        }

        return $this->render('admin/gestion_events/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/edit/{id}', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Event $event, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PSYCHOLOGUE');

        $oldImage = $event->getImage();
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle image upload
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $imagePath = $this->handleEventImageUpload($imageFile, $oldImage);
                $event->setImage($imagePath);
            }

            $em->flush();

            $this->addFlash('success', 'Evenement mis a jour avec succes !');

            return $this->redirectToRoute('admin_events_index');
        }

        return $this->render('admin/gestion_events/edit.html.twig', [
            'form' => $form->createView(),
            'event' => $event,
        ]);
    }

    #[Route('/show/{id}', name: 'show', methods: ['GET'])]
    public function show(Event $event, EventReservationRepository $reservationRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PSYCHOLOGUE');

        $reservations = $reservationRepository->findBy(['event' => $event], ['dateReservation' => 'DESC']);
        $activeCount = $reservationRepository->countActiveByEvent($event);

        return $this->render('admin/gestion_events/show.html.twig', [
            'event' => $event,
            'reservations' => $reservations,
            'activeCount' => $activeCount,
            'totalSpaces' => $event->getCapacite(),
            'availableSpaces' => max(0, $event->getCapacite() - $activeCount),
        ]);
    }

    #[Route('/delete/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Event $event, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PSYCHOLOGUE');

        if ($this->isCsrfTokenValid('delete_event_' . $event->getId(), (string) $request->request->get('_token'))) {
            $em->remove($event);
            $em->flush();
            $this->addFlash('success', 'Evenement supprime avec succes !');
        } else {
            $this->addFlash('error', 'Token CSRF invalide, suppression annulee !');
        }

        return $this->redirectToRoute('admin_events_index');
    }
}
