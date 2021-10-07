<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiControllerTest extends WebTestCase
{
    /**
     * Sprawdzanie czy na stronie z produktami liczba wyświetlonych produktów jest między 0 a 3
     */
    public function testProductsList()
    {
        $client = static::createClient();
        for($i=1; $i<5;$i++)
        {
            $crawler = $client->request('GET', '/api/products/'.$i);

            $this->assertResponseIsSuccessful();
            $content=json_decode($client->getResponse()->getContent());
            $this->assertLessThanOrEqual(3,sizeof($content));
            $this->assertGreaterThanOrEqual(0,sizeof($content));
        }

    }
}
