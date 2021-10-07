<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\User;
use App\Service\CartService;
use App\Service\ProductService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/cart")
 */
class CartController extends AbstractController
{

    /**
     * @var CartService
     */
    private $cartService;
    /**
     * @var ProductService
     */
    private $productService;

    /**
     * @param CartService $cartService
     * @param ProductService $productService
     */
    public function __construct(CartService $cartService, ProductService $productService)
    {
        $this->cartService = $cartService;
        $this->productService = $productService;
    }

    /**
     * @Route("/add", name="add_cart", methods={"POST"})
     * @return JsonResponse
     */
    public function createAction(): JsonResponse
    {
        /**
         * @var $user User
         */
        $user=$this->getUser();
        $cart=$this->cartService->getUserCart($user);

        if(is_null($cart))
        {
            $this->cartService->createCart($user);
            return new JsonResponse(array('status'=>'Cart created'),201);
        }else{
            return new JsonResponse(array('status'=>'Cart already exist'),200);
        }
    }

    /**
     * @Route("/show", name="my_cart", methods={"GET"})
     * @return JsonResponse
     */
    public function showAction(): JsonResponse
    {
        /** @var User $user */
        $user=$this->getUser();
        /** @var Cart|null $cart */
        $cart=$this->cartService->getUserCart($user);
        if(is_null($cart))
        {
            return $this->json(array('status'=>'error','message'=>"Cart dont exist"),400);
        }

        if($cart->getItems()===0)
        {
            return $this->json(array('status'=>'info','message'=>'Cart is empty','cart_info'=>$this->cartService->getCartSummary($cart)));
        }

        return $this->json(array('status'=>'info','cart_info'=>$this->cartService->getCartSummary($cart),'products'=>$this->cartService->getCartProducts($cart)));
    }

    /**
     * @Route("/add_to_cart", name="add_to_cart", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     */
    public function addToCartAction(Request $request): JsonResponse
    {
        if ($request->getContentType() != 'json' || !$request->getContent()) {
            return $this->json(array('message'=>'Request must be json type'),400);
        }
        /** @var User $user */
        $user=$this->getUser();
        $em=$this->getDoctrine()->getManager();
        /** @var Cart|null $cart */
        $cart=$this->cartService->getUserCart($user);
        if(is_null($cart))
        {
            return $this->json(array('status'=>'error','message'=>"Cart dont exist"),400);
        }
        $data = json_decode($request->getContent(), true);
        $product=$this->productService->getProduct($data['product']);
        if(is_null($product))
        {
            return $this->json(array('status'=>'error','message'=>"Product dont exist"),400);
        }
        // sprawdzam ilość pozycji w koszyku
        if($cart->getItems()==3)
        {
            return $this->json(array('status'=>'info','message'=>'Cart is full','cart_info'=>$this->cartService->getCartSummary($cart)));
        }
        // sprawdzam czy produkt jest juz w koszyku
        /** @var CartItem|null $item */
        $item=$this->cartService->getCartItemWithProduct($cart,$product);
        if(is_null($item))
        {
            $item=new CartItem($product);
            $cart->addCartItem($item);
            $cart->setItems($cart->getItems()+1);
            $em->persist($item);
            $em->persist($cart);
            $em->flush();

            return $this->json(array('status'=>'product added','cart_info'=>$this->cartService->getCartSummary($cart)));
        }
        return $this->json(array('status'=>'product already in cart','cart_info'=>$this->cartService->getCartSummary($cart)));
    }

    /**
     * @Route("/delete_product", name="remove_from_cart", methods={"DELETE"})
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteFromCartAction(Request $request): JsonResponse
    {
        if ($request->getContentType() != 'json' || !$request->getContent()) {
            return $this->json(array('message'=>'Request must be json type'),400);
        }
        /** @var User $user */
        $user=$this->getUser();
        /** @var Cart|null $cart */
        $cart=$this->cartService->getUserCart($user);
        if(is_null($cart))
        {
            return $this->json(array('status'=>'error','message'=>"Cart dont exist"),400);
        }
        $data = json_decode($request->getContent(), true);
        $product=$this->productService->getProduct($data['product']);
        if(is_null($product))
        {
            return $this->json(array('status'=>'error','message'=>"Product dont exist"),400);
        }
        // sprawdzam ilość pozycji w koszyku
        if($cart->getItems()==0)
        {
            return $this->json(array('status'=>'info','message'=>'Cart is empty'));
        }
        // sprawdzam czy produkt jest juz w koszyku
        /** @var CartItem|null $item */
        $item=$this->cartService->getCartItemWithProduct($cart,$product);
        $em=$this->getDoctrine()->getManager();
        if(is_null($item))
        {
            return $this->json(array('status'=>'error','message'=>'Product is not in the cart'));
        }
        $em->remove($item);
        $cart->setItems($cart->getItems()-1);
        $em->flush();
        return $this->json(array('status'=>'Product removed from cart','cart_info'=>$this->cartService->getCartSummary($cart)));
    }

}
