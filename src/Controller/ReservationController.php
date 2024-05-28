<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Reservation;
use App\Form\ReservationType;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ReservationController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(EntityManagerInterface $entityManager, UserInterface $user): Response
    {
        $reservation = $entityManager->getRepository(Reservation::class)->findRdvproche($user);
        
        return $this->render('reservation/index.html.twig', [
            'reservation' => $reservation
        ]);
    }

    #[Route('/reservation/new', name: 'app_reservation_new')]
    public function new(Request $request, EntityManagerInterface $entityManager, UserInterface $user): Response
    {
        $reservation = new Reservation();
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            $reservation->setEtudiant($user);

            // Check if the selected datetime is available for the instructor
            $existingReservation = $entityManager->getRepository(Reservation::class)
                ->findOneBy(['date' => $reservation->getDate(), 'instructeur' => $reservation->getInstructeur()]);

            if ($existingReservation) {
                $this->addFlash('danger', 'L\'instructeur n\'est pas disponible à cette date et heure.');
                return $this->redirectToRoute('app_reservation_new');
            }

            $entityManager->persist($reservation);
            $entityManager->flush();

            return $this->redirectToRoute('app_reservation_pay', ['id' => $reservation->getId()]);
        }
        
        return $this->render('reservation/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/reservations', name: 'app_reservations')]
    public function all(EntityManagerInterface $entityManager, UserInterface $user): Response
    {
        if ($this->isGranted('ROLE_ETUDIANT')) {
            $reservations = $entityManager->getRepository(Reservation::class)->findByEtudiant($user);
        }elseif ($this->isGranted('ROLE_INSTRUCTEUR')){
            $reservations = $entityManager->getRepository(Reservation::class)->findByInstructeur($user);
        }

        return $this->render('reservation/reservations.html.twig', [
            'reservations' => $reservations
        ]);
    }

    #[Route('/reservation/payment/{id}', name: 'app_reservation_pay')]
    public function pay(Reservation $reservation): Response 
    {
        if ($reservation->isPayer()) {
            return $this->redirectToRoute('app_reservation_success');
        }

        Stripe::setApiKey($this->getParameter('stripe_secret_key'));

        $session = StripeSession::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => 'Permis de conduite Reservation',
                    ],
                    'unit_amount' => 1000,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $this->generateUrl('app_payment_success', ['id' => $reservation->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $this->generateUrl('app_payment_cancel', ['id' => $reservation->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        return $this->redirect($session->url, 303);
    }

    #[Route('/reservation/payment/success/{id}', name: 'app_payment_success')]
    public function success(Reservation $reservation, EntityManagerInterface $entityManager): Response 
    {
        $reservation->setPayer(true);
        $entityManager->persist($reservation);
        $entityManager->flush();

        return $this->render('reservation/success.html.twig');
    }

    #[Route('/reservation/payment/cancel/{id}', name: 'app_payment_cancel')]
    public function cancel(Reservation $reservation, EntityManagerInterface $entityManager): Response 
    {
        $this->addFlash('danger', 'Le paiement a été annulé.');
        return $this->redirectToRoute('app_reservation_new');
    }
}
