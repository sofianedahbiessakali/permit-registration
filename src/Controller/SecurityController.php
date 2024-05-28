<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Entity\Etudiant;
use App\Form\EtudiantType;
use Doctrine\ORM\EntityManagerInterface;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error'         => $error,
        ]);
    }

    #[Route('/app_register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, ValidatorInterface $validator, EntityManagerInterface $entityManager): Response
    {
        $etudiant = new Etudiant();
        $form = $this->createForm(EtudiantType::class, $etudiant);

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            
            $etudiant = $form->getData();

            $etudiant->setPassword(
                $userPasswordHasher->hashPassword(
                    $etudiant,
                    $form->get('password')->getData()
                )
            );

            $errors = $validator->validate($etudiant);

            if (count($errors) > 0) {
                //$errorsString = (string) $errors;
                return $this->render('security/register.html.twig', [
                    'formuser' => $form->createView(), 'errors' => $errors
                ]);
            }
 
            $entityManager->persist($etudiant);
            $entityManager->flush();

            return $this->redirectToRoute('app_login');
        }
        return $this->render('security/register.html.twig', [
            'formuser' => $form->createView(),
        ]);
    }
    
    #[Route('/app_logout', name: 'app_logout')]
    public function logout() : Response {
        
    }
}
