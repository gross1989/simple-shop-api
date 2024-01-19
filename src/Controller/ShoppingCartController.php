<?php

namespace App\Controller;

use App\Entity\Item;
use App\Entity\ShoppingCart;
use App\Repository\ItemRepository;
use App\Repository\ShoppingCartRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class ShoppingCartController extends AbstractController
{
    #[Route('/shopping-cart/{shoppingCartId}', name: 'show_shopping_cart', methods: ['GET'])]
    public function index(ShoppingCartRepository $shoppingCartRepository, int $shoppingCartId): JsonResponse
    {
        $shoppingCart = $shoppingCartRepository->find($shoppingCartId);
        if ($shoppingCart === null) {
            return $this->json([
                'errors' => 'shopping cart not found',
            ], 404);
        }
        return $this->json($shoppingCart);
    }

    #[Route('/shopping-cart/{shoppingCartId?}', name: 'add_item', methods: ['POST'])]
    public function create(SerializerInterface    $serializer,
                           EntityManagerInterface $entityManager,
                           Request                $request,
                           ShoppingCartRepository $shoppingCartRepository,
                           ?int                   $shoppingCartId): JsonResponse
    {
        if ($shoppingCartId === null) {
            $shoppingCart = new ShoppingCart();
        } else {
            $shoppingCart = $shoppingCartRepository->find($shoppingCartId);
            if ($shoppingCart === null) {
                return $this->json([
                    'errors' => 'shopping cart not found',
                ], 404);
            }
        }

        try {
            $content = $request->getContent();
            $item = $serializer->deserialize($content, Item::class, 'json');
            $shoppingCart->addItem($item);

            $entityManager->persist($item);
            $entityManager->persist($shoppingCart);
            $entityManager->flush();
        } catch (Exception $exception) {
            return $this->json([
                'errors' => $exception->getMessage(),
            ], 404);
        }

        return $this->json([
            'shopping_cart_id' => $shoppingCart->getId()
        ]);
    }

    #[Route('/shopping-cart/{itemId}', name: 'edit_item', methods: ['PUT'])]
    public function edit(ItemRepository         $itemRepository,
                         SerializerInterface    $serializer,
                         EntityManagerInterface $entityManager,
                         Request                $request,
                         int                    $itemId): JsonResponse
    {
        $item = $itemRepository->find($itemId);
        if ($item === null) {
            return $this->json([
                'errors' => 'item not found',
            ], 404);
        }

        try {
            $item = $serializer->deserialize(
                $request->getContent(),
                Item::Class,
                'json',
                [
                    AbstractNormalizer::OBJECT_TO_POPULATE => $item
                ]
            );
            $entityManager->persist($item);
            $entityManager->flush();
        } catch (Exception $exception) {
            return $this->json([
                'errors' => $exception->getMessage(),
            ], 404);
        }

        return $this->json([
            'item_id' => $item->getId()
        ]);
    }

    #[Route('/shopping-cart/{itemId}', name: 'delete_item', methods: ['DELETE'])]
    public function destroy(EntityManagerInterface $entityManager,
                            ItemRepository         $itemRepository,
                            int                    $itemId): JsonResponse
    {
        $item = $itemRepository->find($itemId);
        if ($item === null) {
            return $this->json([
                'errors' => 'item not found',
            ], 404);
        }
        $entityManager->remove($item);
        $entityManager->flush();

        return $this->json([
            'item_id' => $itemId
        ]);
    }
}
