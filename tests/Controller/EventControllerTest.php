<?php

namespace App\Tests\Controller;

use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class EventControllerTest extends WebTestCase
{
    public function testHome(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertCount(3, $crawler->filter('.event-card'));
        $this->assertSelectorNotExists('.event-card:contains("Concert passé")');
        $this->assertSelectorNotExists('.event-card:contains("Tournoi de basket")');
    }

   public function testShowDetail(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $link = $crawler->selectLink('Soirée jazz')->link();
        $crawler = $client->click($link);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Soirée jazz'); // Changé ici pour correspondre au texte exact du h1
    } 
    public function testNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/evenement/9999');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testCategoryFilter(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/?categorie=sport');

        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('.event-card'));
        $this->assertSelectorExists('a.active:contains("Sport")');
    }

    public function testEventFormErrors(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/evenement/ajouter');

       
        $form = $crawler->selectButton('Enregistrer')->form([
            'event[title]' => '',
            'event[places]' => 0,
        ]);
        
        $crawler = $client->submit($form);

        
        $this->assertSelectorExists('.invalid-feedback, .alert-danger, ul li');
    }
}