<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Aws\S3\S3Client;


class HomeController extends AbstractController
{
    #[Route(path: '/', name: 'app_landing')]
    public function landing(): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        return $this->render('home/landing.html.twig', []);
    }

    #[Route(path: '/home', name: 'app_home')]
    public function home(EntityManagerInterface $entityManager): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_landing');
        }

        if ($this->getUser()->isIsCompleted() === false) {
            return $this->redirectToRoute('app_edit');
        }

        return $this->render('home/home.html.twig', []);
    }

    #[Route(path: '/edit', name: 'app_edit', methods: ['GET'])]
    public function edit(): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_register');
        }

        return $this->render('home/complete.html.twig', []);
    }

    #[Route(path: '/edit', methods: ['POST'])]
    public function editProcess(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_register');
        }

        $user = $this->getUser();
        $user->setFirstName($request->request->get('first_name'));
        $user->setLastName($request->request->get('last_name'));
        $user->setSex($request->request->get('sex'));
        $user->setIsCompleted(true);

        $entityManager->persist($user);
        $entityManager->flush();

        return $this->redirectToRoute('app_home');
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
