<?php

namespace Air\BlogBundle\Twig\Extension;

use Symfony\Component\Security\Core\SecurityContext;


class BlogExtension extends \Twig_Extension
{
    /**
     * @var \Doctrine\Bundle\DoctrineBundle\Registry
     */
    private $doctrine;

    /**
     * @var SecurityContext
     */
    private $securityContext;

    private $categoriesList;

    /**
     * @var \Twig_Environment
     */
    private $environment;

    public function initRuntime(\Twig_Environment $environment)
    {
        $this->environment = $environment;
    }

    function __construct($doctrine, SecurityContext $securityContext)
    {
        $this->doctrine = $doctrine;
        $this->securityContext = $securityContext;
    }


    public function getName()
    {
        return 'air_blog_extension';
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('print_categories_list',
                array($this, 'printCategoriesList'),
                array('is_safe' => array('html'))),
            new \Twig_SimpleFunction('print_main_menu',
                array($this, 'printMainMenu'),
                array('is_safe' => array('html'))),
            new \Twig_SimpleFunction('print_tags_cloud',
                array($this, 'tagsCloud'),
                array('is_safe' => array('html'))),
            new \Twig_SimpleFunction('print_recent_commented',
                array($this, 'recentCommented'),
                array('is_safe' => array('html'))),
        );
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('ab_shorten', array($this, 'shorten'), array('is_safe' => array('html'))),
        );
    }

    public function printCategoriesList()
    {
        if(!isset($this->categoriesList)) {
            $categoryRepo = $this->doctrine->getRepository('AirBlogBundle:Category');
            $this->categoriesList = $categoryRepo->findAll();
        }


        return $this->environment->render('AirBlogBundle:Template:categoriesList.html.twig', array(
           'categoriesList' =>  $this->categoriesList,
        ));
    }

    public function printMainMenu()
    {
        $mainMenu = array(
            'home'      =>  'blog_index',
            'o mnie'    =>  'blog_about',
            'kontakt'   =>  'blog_contact'
        );

        if($this->securityContext->isGranted('ROLE_EDITOR')) {
            $mainMenu['admin'] = 'admin_dashboard';
        }

        return $this->environment->render('AirBlogBundle:Template:mainMenu.html.twig', array(
            'mainMenu'  =>  $mainMenu,
        ));
    }

    public function tagsCloud($limit = 40, $minFontSize = 1, $maxFontSize = 3.5)
    {
        $tagRepo = $this->doctrine->getRepository('AirBlogBundle:Tag');
        $tagList = $tagRepo->getTagsListOcc();
        $tagsList = $this->prepareTagsCloud($tagList, $limit, $minFontSize, $maxFontSize);

        return $this->environment->render('AirBlogBundle:Template:tagsCloud.html.twig', array(
            'tagsList'  =>  $tagsList,
        ));
    }

    protected function prepareTagsCloud($tagsList, $limit, $minFontSize, $maxFontSize){
        $occs = array_map(function($row){
            return (int)$row['occ'];
        }, $tagsList);

        $minOcc = min($occs);
        $maxOcc = max($occs);

        $spread = $maxOcc - $minOcc;

        $spread = ($spread == 0) ? 1 : $spread;

        usort($tagsList, function($a, $b){
            $ao = $a['occ'];
            $bo = $b['occ'];

            if($ao === $bo) return 0;

            return ($ao < $bo) ? 1 : -1;
        });

        $tagsList = array_slice($tagsList, 0, $limit);

        shuffle($tagsList);

        foreach($tagsList as &$row){
            $row['fontSize'] = round(($minFontSize + ($row['occ'] - $minOcc) * ($maxFontSize - $minFontSize) / $spread), 2);
        }

        return $tagsList;
    }

    public function shorten($text, $length = 200, $wrapTag = 'p')
    {
        $text = strip_tags($text);
        $text = substr($text, 0, $length) . '[...]';
        $openTag = "<{$wrapTag}>";
        $closeTag = "</{$wrapTag}>";

        return $openTag . $text . $closeTag;
    }

    public function recentCommented($limit = 3){

        $PostRepo = $this->doctrine->getRepository('AirBlogBundle:Post');

        $postsList = $PostRepo->getRecentCommented($limit);

        return $this->environment->render('AirBlogBundle:Template:recentCommented.html.twig', array(
            'postsList' => $postsList
        ));
    }
}