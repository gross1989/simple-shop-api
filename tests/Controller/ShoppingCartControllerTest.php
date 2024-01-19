<?php

namespace App\Tests\Controller;

use App\Entity\Item;
use App\Entity\ShoppingCart;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ShoppingCartControllerTest extends WebTestCase
{
    public function testAddItemToEmptyShoppingCart()
    {
        $client = self::createClient();
        $entityManager = self::getContainer()->get('doctrine')->getManager();

        $client->jsonRequest('POST', '/shopping-cart', [
            'title' => 'test item one',
            'price' => '123.45'
        ]);
        $this->assertResponseIsSuccessful();

        $content = $client->getResponse()->getContent();
        $shoppingCartId = json_decode($content)->shopping_cart_id ?? null;
        $this->assertNotNull($shoppingCartId);

        $item = $entityManager->getRepository(Item::class)
            ->findOneBy(['title' => 'test item one']);
        $this->assertNotEmpty($item);
    }

    public function testAddSecondItemToShoppingCart()
    {
        $client = self::createClient();
        $entityManager = self::getContainer()->get('doctrine')->getManager();

        $shoppingCart = $entityManager->getRepository(ShoppingCart::class)->findOneBy([]);
        $client->jsonRequest('POST', '/shopping-cart/' . $shoppingCart->getId(), [
            'title' => 'test item two',
            'price' => '678.90'
        ]);
        $this->assertResponseIsSuccessful();

        $itemTwo = $entityManager->getRepository(Item::class)
            ->findOneBy(['title' => 'test item two']);
        $this->assertNotEmpty($itemTwo);
    }

    public function testDeleteItemFromShoppingCart()
    {
        $client = self::createClient();
        $entityManager = self::getContainer()->get('doctrine')->getManager();

        $itemOne = $entityManager->getRepository(Item::class)
            ->findOneBy(['title' => 'test item one']);
        $itemOneId = $itemOne->getId();
        $client->jsonRequest('DELETE', '/shopping-cart/' . $itemOneId);
        $this->assertResponseIsSuccessful();

        $deletedItem = $entityManager->getRepository(Item::class)
            ->find($itemOneId);
        $this->assertEmpty($deletedItem);
    }

    public function testEditItemInShoppingCart()
    {
        $client = self::createClient();
        $entityManager = self::getContainer()->get('doctrine')->getManager();

        $itemTwo = $entityManager->getRepository(Item::class)
            ->findOneBy(['title' => 'test item two']);
        $client->jsonRequest('PUT', '/shopping-cart/' . $itemTwo->getId(), [
            'quantity' => 2
        ]);
        $this->assertEquals(2, $itemTwo->getQuantity());
    }

    public function testShowShoppingCart()
    {
        $client = self::createClient();
        $entityManager = self::getContainer()->get('doctrine')->getManager();

        $shoppingCart = $entityManager->getRepository(ShoppingCart::class)->findOneBy([]);
        $client->jsonRequest('GET', '/shopping-cart/' . $shoppingCart->getId());
        $this->assertResponseIsSuccessful();

        $content = $client->getResponse()->getContent();
        $items = json_decode($content)->items ?? null;
        $this->assertCount(1, $items);
    }

    public function testFailureShowRemovedShoppingCart()
    {
        $client = self::createClient();
        $entityManager = self::getContainer()->get('doctrine')->getManager();

        $shoppingCart = $entityManager->getRepository(ShoppingCart::class)->findOneBy([]);
        $shoppingCartId = $shoppingCart->getId();
        $entityManager->remove($shoppingCart);
        $entityManager->flush();

        $client->jsonRequest('GET', '/shopping-cart/' . $shoppingCartId);
        $this->assertResponseStatusCodeSame(404);
    }
}
