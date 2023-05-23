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

        $image = $request->files->get('image');
        $extension = explode('.', $image->getClientOriginalName());
        $extension = end($extension);
        $fileName = $this->getUser()->getId() . $this->getUser()->getfirstName() . '.' .  $extension;
        $image->move($kernel->getProjectDir() . '/public/assets/images/temp', $fileName);

        $s3 = new S3Client([
            'region'  => 'eu-north-1',
            'version' => 'latest',
            'credentials' => [
                'key'    => "AKIAQLE734WSU6D7PZ4O",
                'secret' => "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",
            ]
        ]);

//        $sha256 = hash_file("sha256", $kernel->getProjectDir() . '/public/assets/images/temp/' . $fileName);
        $result = $s3->putObject([
            'Bucket' => 'awsprojectnicolescuandreibucket',
            'Key'    => $fileName,
            'SourceFile' => $kernel->getProjectDir() . '/public/assets/images/temp/'. $fileName, $fileName,
//            "ContentSHA256" => $sha256,
        ]);

        $topic = new Topic();
        $topic->setTitle($request->request->get('title'));
        $topic->setDescription($request->request->get('description'));
        $topic->setCreatedAt(new \DateTimeImmutable());
        $topic->setImageUrl('https://awsprojectnicolescuandreibucket.s3.eu-north-1.amazonaws.com/' . $fileName);
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
