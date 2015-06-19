<?php

namespace Air\BlogBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Air\BlogBundle\Entity\Tag;

class TagsFixtures extends AbstractFixture implements OrderedFixtureInterface
{

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {

        $tagsList = array(
            'dolor',
            'ullamcorper',
            'suspendisse',
            'pellentesque',
            'maecenas',
            'malesuada',
            'ultricies',
            'etiam',
            'quisque',
            'fringilla',
            'eleifend',
            'bibendum',
            'faucibus',
            'luctus',
            'vestibulum'
        );

        foreach ($tagsList as $key => $name) {
            $tag = new Tag();
            $tag->setName($name);

            $manager->persist($tag);
            $this->addReference('tag_' . $name, $tag);
        }

        $manager->flush();

    }

    public function getOrder()
    {
        return 0;
    }
}