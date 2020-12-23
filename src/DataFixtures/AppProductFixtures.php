<?php

namespace App\DataFixtures;

use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppProductFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $names=array('The Godfather','Steve Jobs','The Return of Sherlock Holmes','The Little Prince','I Hate Myselfie!','The Trial');
        $prices=array(59.99,49.95,39.99,29.99,19.99,9.99);
        for($i=0;$i<6;$i++)
        {
            $product=new Product();
            $product->setName($names[$i])->setPrice($prices[$i])->setCurrency('PLN');
            $manager->persist($product);
        }
        $manager->flush();
    }
}
