<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Entity\Post;
use Symfony\Component\HttpFoundation\Request;
use App\Form\PostFormType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use App\Entity\Comment;
use App\Form\CommentFormType;

class BlogController extends AbstractController
{
    #[Route('/blog', name: 'blog')]
    public function indexOld(): Response
    {
        return $this->render('blog/blog.html.twig', [
            'controller_name' => 'BlogController',
        ]);
    }

    #[Route('/blog/{page}', name: 'blog', requirements: ['page' => '\d+'])]
    public function index(ManagerRegistry $doctrine, int $page = 1): Response
    {
        $repository = $doctrine->getRepository(Post::class);
        $posts = $repository->findAll($page);

        return $this->render('blog/blog.html.twig', [
            'posts' => $posts,
        ]);
    }


     /**
     * @Route("/blog/new", name="new_post")
     */
    public function newPost(ManagerRegistry $doctrine, Request $request, SluggerInterface $slugger): Response
    {
        $post = new Post();
        $form = $this->createForm(PostFormType::class, $post);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('image')->getData();
            if ($file) {
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();
        
                // Move the file to the directory where images are stored
                try {
                    
                    $file->move(
                        $this->getParameter('post_image_directory'), $newFilename
                    );
                   
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }
                $post->setImage($newFilename);
            }
        
            $post = $form->getData();   
            $post->setSlug($slugger->slug($post->getTitle()));
            $post->setPostUser($this->getUser());
            $post->setNumLikes(0);
            $post->setNumComments(0);
            $entityManager = $doctrine->getManager();    
            $entityManager->persist($post);
            $entityManager->flush();
            return $this->redirectToRoute('single_post', ["slug" => $post->getSlug()]);
        }
        return $this->render('blog/new_post.html.twig', array(
            'form' => $form->createView()    
        ));
    }

    #[Route('/single_post/{slug}', name: 'single_post')]
public function post(ManagerRegistry $doctrine, Request $request, $slug): Response
{
    $repository = $doctrine->getRepository(Post::class);
    $post = $repository->findOneBy(["slug"=>$slug]);
    $recents = $repository->findRecents();
    $comment = new Comment();
    $form = $this->createForm(CommentFormType::class, $comment);
    $form->handleRequest($request);
    if ($form->isSubmitted() && $form->isValid()) {
        $comment = $form->getData();
        $comment->setPost($post);  
        //Aumentamos en 1 el número de comentarios del post
        $post->setNumComments($post->getNumComments() + 1);
        $entityManager = $doctrine->getManager();    
        $entityManager->persist($comment);
        $entityManager->flush();
        return $this->redirectToRoute('single_post', ["slug" => $post->getSlug()]);
    }
    return $this->render('blog/single_post.html.twig', [
        'post' => $post,
        'recents' => $recents,
        'commentForm' => $form->createView()
    ]);
}
}