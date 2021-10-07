<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class CartService
{

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getUserCart(User $user):?Cart
    {
        return $this->entityManager->getRepository(Cart::class)->getActiveUserCart($user);
    }

    public function createCart(User $user):void
    {
        $cart =new Cart();
        $cart->setUser($user);
        $this->entityManager->persist($cart);
        $this->entityManager->flush();
    }

    public function getCartItemWithProduct(Cart $cart,Product $product)
    {
        return $this->entityManager->getRepository(CartItem::class)->getCartItemWithProduct($cart, $product);
    }

    public function getCartSummary(Cart $cart): array
    {
        $total=0;
        foreach ($cart->getCartItems() as $item)
        {
            $total+=$item->getProduct()->getPrice();
        }
        return array('items'=>$cart->getItems(),'total'=>round($total,2));
    }

    public function getCartProducts(Cart $cart): array
    {
        $products=array();
        foreach ($cart->getCartItems() as $item)
        {
            array_push($products,array(
                    'id'=>$item->getProduct()->getId(),
                    'name'=>$item->getProduct()->getName(),
                    'price'=>$item->getProduct()->getPrice(),
                    'currency'=>$item->getProduct()->getCurrency(),
                )
            );
        }
        return $products;
    }

}