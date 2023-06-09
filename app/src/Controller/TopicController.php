<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Topic;
use Aws\S3\S3Client;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

class TopicController extends AbstractController
{
    #[Route(path: '/feed', name: 'app_feed', methods: ['GET'])]
    public function home(EntityManagerInterface $entityManager): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_landing');
        }

        $topics = $entityManager->getRepository(Topic::class)->findAll();

        return $this->render('home/feed.html.twig', [
            'topics' => $topics,
        ]);
    }

    #[Route(path: '/topic/create', methods: ['GET'])]
    public function topicCreate(EntityManagerInterface $entityManager): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_landing');
        }

        return $this->render('home/topic_data.html.twig', []);
    }

    #[Route(path: '/topic/create', methods: ['POST'])]
    public function topicCreateProcess(EntityManagerInterface $entityManager, Request $request, KernelInterface $kernel): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_landing');
        }

        $topic = new Topic();
        $topic->setImageUrl(null);

        if ($request->files->get('image') !== null) {
            $image = $request->files->get('image');
            $extension = explode('.', $image->getClientOriginalName());
            $extension = end($extension);
            $fileName = $this->getUser()->getId() . $this->getUser()->getfirstName() . $image->getClientOriginalName() . '.' .  $extension;
            $image->move($kernel->getProjectDir() . '/public/assets/images/temp', $fileName);
            $topic->setImageUrl('/assets/images/temp/' . $fileName);
        }

        $topic->setTitle($request->request->get('title'));
        $topic->setDescription($request->request->get('description'));
        $topic->setCreatedAt(new \DateTimeImmutable());
        $topic->setOwner($this->getUser());

        $entityManager->persist($topic);
        $entityManager->flush();

        return $this->redirectToRoute('topic_view', [
            'id' => $topic->getId(),
        ]);
    }

    #[Route(path: '/topic/{id}', name: 'topic_view', methods: ['GET'])]
    public function topicView(EntityManagerInterface $entityManager, int $id): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_landing');
        }

        $topic = $entityManager->getRepository(Topic::class)->findOneBy(['id' => $id]);
        $comments = $entityManager->getRepository(Comment::class)->findBy(['topic' => $topic]);

        return $this->render('home/topic_view.html.twig', [
            'topic' => $topic,
            'comments' => $comments,
        ]);
    }

    #[Route(path: '/topic/{id}', methods: ['POST'])]
    public function topicComment(EntityManagerInterface $entityManager, int $id, Request $request): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_landing');
        }

        $topic = $entityManager->getRepository(Topic::class)->findOneBy(['id' => $id]);

        $comment = new Comment();
        $comment->setText($request->request->get('comment'));
        $comment->setTopic($topic);
        $comment->setOwner($this->getUser());
        $comment->setCreatedAt(new \DateTimeImmutable());

        $entityManager->persist($comment);
        $entityManager->flush();

        return $this->redirectToRoute('topic_view', [
            'id' => $topic->getId(),
        ]);
    }
}
