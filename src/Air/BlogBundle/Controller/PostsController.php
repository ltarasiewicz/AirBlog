<?php

namespace Air\BlogBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Air\BlogBundle\Entity\Comment;
use Air\BlogBundle\Form\Type\CommentType;

class PostsController extends Controller
{
    protected $itemsLimit = 3;
    /**
     * @Route(
     *      "/{page}",
     *      name = "blog_index",
     *      defaults = {"page" = 1},
     *      requirements = {"page" = "\d+"}
     * )
     * @Template("AirBlogBundle:Posts:postsList.html.twig")
     */
    public function indexAction($page)
    {
        $pagination = $this->getPaginatedPosts(array(
            'status'        =>  'published',
            'orderBy'       =>  'p.publishedDate',
            'orderDir'      =>  'DESC',
        ), $page);

        return array(
            'pagination'    => $pagination,
            'listTitle'     => 'Najnowesz wpisy'
        );
    }

    /**
     * @Route(
     *      "/search/{page}",
     *      name = "blog_search",
     *      defaults = {"page" = 1},
     *      requirements = {"page" = "\d+"}
     * )
     * @Template("AirBlogBundle:Posts:postsList.html.twig")
     */
    public function searchAction(Request $request, $page)
    {
        $searchParam = $request->get('search');

        $pagination = $this->getPaginatedPosts(array(
            'status'        =>  'published',
            'orderBy'       =>  'p.publishedDate',
            'orderDir'      =>  'DESC',
            'search'        =>  $searchParam,
        ), $page);

        return array(
            'pagination'    =>  $pagination,
            'listTitle'     =>  sprintf('Wyniki wyszukiwania dle "%s"', $searchParam),
            'searchParam'   =>  $searchParam,
        );
    }

    /**
     * @Route(
     *      "/{slug}",
     *      name = "blog_post"
     * )
     * @Template()
     */
    public function postAction(Request $request, $slug)
    {
        $postRepo = $this->getDoctrine()->getRepository('AirBlogBundle:Post');
        $post = $postRepo->getPublishedPost($slug);
        if(null === $post) {
            throw $this->createNotFoundException('Post nie został odnaleziony');
        }

        if(null !== $this->getUser()) {

            $comment = new Comment();
            $comment->setAuthor($this->getUser())
                        ->setPost($post);

            $commentForm = $this->createForm(new CommentType(), $comment);

            if($request->isMethod('POST')) {
                $commentForm->handleRequest($request);

                if($commentForm->isValid()) {
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($comment);
                    $em->flush();

                    $this->get('session')->getFlashBag()->add('success', 'Komentarz dodant!');
                    $redirectUrl = $this->generateUrl('blog_post', array(
                        'slug' => $post->getSlug()
                    ));

                    return $this->redirect($redirectUrl);
                }

            }

        }

        if($this->get('security.context')->isGranted('ROLE_ADMIN')) {
            $csrfProvider = $this->get('form.csrf_provider');
        }

        return array(
            'post'  =>  $post,
            'commentForm' => isset($commentForm) ? $commentForm->createView() : null,
            'csrfProvider' => isset($csrfProvider) ? $csrfProvider : null,
            'tokenName' => 'delCom%d',
        );
    }

    /**
     * @Route(
     *      "/category/{slug}/{page}",
     *      name = "blog_category",
     *      defaults = {"page" = 1},
     *      requirements = {"page" = "\d+"}
     * )
     * @Template("AirBlogBundle:Posts:postsList.html.twig")
     */
    public function categoryAction($slug, $page)
    {
        $categoryRepo = $this->getDoctrine()->getRepository('AirBlogBundle:Category');
        $category = $categoryRepo->findOneBySlug($slug);

        $pagination = $this->getPaginatedPosts(array(
            'status'        =>  'published',
            'orderBy'       =>  'p.publishedDate',
            'orderDir'      =>  'DESC',
            'categorySlug'  =>  $slug,
        ), $page);

        return array(
            'pagination' => $pagination,
            'listTitle' => sprintf('Wpisy w kategorii %s', $category->getName()),
        );
    }

    /**
     * @Route(
     *      "/tag/{slug}/{page}",
     *      name = "blog_tag",
     *      defaults = {"page" = 1},
     *      requirements = {"page" = "\d+"}
     * )
     * @Template("AirBlogBundle:Posts:postsList.html.twig")
     */
    public function tagAction($slug, $page)
    {
        $tagRepo = $this->getDoctrine()->getRepository('AirBlogBundle:Tag');
        $tag = $tagRepo->findOneBySlug($slug);

        $pagination = $this->getPaginatedPosts(array(
            'status'        =>  'published',
            'orderBy'       =>  'p.publishedDate',
            'orderDir'      =>  'DESC',
            'tagSlug'       =>  $slug,
        ), $page);

        return array(
            'pagination' => $pagination,
            'listTitle' => sprintf('Wpisy z tagiem %s', $tag->getName()),
        );
    }

    protected function getPaginatedPosts(array $params = array(), $page)
    {
        $postRepo = $this->getDoctrine()->getRepository('AirBlogBundle:Post');
        $qb = $postRepo->getQueryBuilder($params);

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($qb, $page, $this->itemsLimit);

        return $pagination;
    }

    /**
     * @Route(
     *      "/post/comment/delete/{commentId}/{token}",
     *      name = "blog_deleteComment"
     * )
     */
    public function deleteCommentAction($commentId, $token) {

        if(!$this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Nie masz uprawnień do usuwania komentarzy');
        }

        $validToken = sprintf('delCom%d', $commentId);

        if(!$this->get('form.csrf_provider')->isCsrfTokenValid($validToken, $token)) {
            $this->createAccessDeniedException('Błędny token akchi');
        }

        $Comment = $this->getDoctrine()
            ->getRepository('AirBlogBundle:Comment')
            ->find($commentId);

        if(null == $Comment){
            throw $this->createNotFoundException('Nie znaleziono takiego komentarza');
        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($Comment);
        $em->flush();

        return new \Symfony\Component\HttpFoundation\JsonResponse(array(
            'status' => 'ok',
            'message' => 'Wiadomość została usunięta'
        ));
    }
}
