<?php

namespace Air\BlogBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Air\BlogBundle\Entity\Category;

class CategoriesFixtures extends AbstractFixture implements OrderedFixtureInterface
{

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {

        $categoriesList = array(
            'osobowe'   =>  'Samoloty osobowa i pasażerskie',
            'odrzutowe' =>  'Samoloty odrzutowe',
            'wojskowe'  =>  'Samoloty wojskowe',
            'kosmiczne' =>  'Promy kosmiczne',
            'tajne'     =>  'Tajne rozwiązania'
        );

        foreach ($categoriesList as $key => $name) {
            $category = new Category();
            $category->setName($name);

            $manager->persist($category);
            $this->addReference('category_' . $key, $category);
        }

        $manager->flush();
    }

    public function getOrder()
    {
        return 0;
    }
}