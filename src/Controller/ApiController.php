<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Product;
use App\Entity\User;
use App\Form\ProductType;
use App\Repository\CartItemRepository;
use App\Repository\CartRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ApiController extends AbstractController
{
    private function getErrorsFromForm(FormInterface $form)
    {
        $errors = array();
        foreach ($form->getErrors() as $error) {
            $errors[] = $error->getMessage();
        }
        foreach ($form->all() as $childForm) {
            if ($childForm instanceof FormInterface) {
                if ($childErrors = $this->getErrorsFromForm($childForm)) {
                    $errors[$childForm->getName()] = $childErrors;
                }
            }
        }
        return $errors;
    }

    /**
     * @Route("/api/product/add", name="add_product", methods={"POST"})
     */
    public function add(Request $request): JsonResponse
    {
        if ($request->getContentType() != 'json' || !$request->getContent()) {
            return $this->json(array('message'=>'Request must be json type'),400);
        }

        $data = json_decode($request->getContent(), true);
        $request->request->replace(is_array($data) ? $data : array());
        $product=new Product();
        $form=$this->createForm(ProductType::class,$product);
        $form->submit($request->request->all());
        $form->handleRequest($request);

        if($form->isSubmitted()&&$form->isValid())
        {
            $em=$this->getDoctrine()->getManager();
            $em->persist($product);
            $em->flush();

            return new JsonResponse(array('status'=>'created','product_id'=>$product->getId()),201);
        }
        $errors = $this->getErrorsFromForm($form);
        $data = [
            'status'=>'error',
            'type' => 'validation_error',
            'title' => 'There was a validation error',
            'errors' => $errors
        ];

        return new JsonResponse(['status' => 'error','errors'=>$data], 400);
    }

    /**
     * @Route("/api/product/{id}/remove", name="remove_product", methods={"DELETE"})
     * @param Product $product
     * @return JsonResponse
     */
    public function remove(Product $product)
    {
        $em=$this->getDoctrine()->getManager();
        $em->remove($product);
        return new JsonResponse(array('status'=>'deleted'),200);
    }


    /**
     * @Route("/api/product/{id}/edit/{type}",name="edit_product", methods={"PATCH"},defaults={"type": "name"},requirements={"type"="name|price"})
     * @param Product $product
     * @param Request $request
     * @return JsonResponse
     */
    public function edit(Product $product,Request $request,string $type)
    {
        if ($request->getContentType() != 'json' || !$request->getContent()) {
            return $this->json(array('message'=>'Request must be json type'),400);
        }

        $data = json_decode($request->getContent(), true);
        $request->request->replace(is_array($data) ? $data : array());
        $form=$this->createForm(ProductType::class,$product);
        switch ($type)
        {
            case 'name':
                $form->remove('price');
                break;
            case 'price':
                $form->remove('name');
                break;
            default:
                break;
        }
        $form->submit($request->request->all());
        $form->handleRequest($request);

        if($form->isSubmitted()&&$form->isValid())
        {
            $em=$this->getDoctrine()->getManager();
            $em->persist($product);
            $em->flush();

            return new JsonResponse(array('status'=>'updated'),200);
        }
        $errors = $this->getErrorsFromForm($form);
        $data = [
            'status'=>'error',
            'type' => 'validation_error',
            'title' => 'There was a validation error',
            'errors' => $errors
        ];

        return new JsonResponse(['status' => 'error','errors'=>$data], 400);
    }


    /**
     * @Route("/api/products/{page}", name="products_list",defaults={"page":1},requirements={"page"="\d+"}, methods={"GET"})
     * @param ProductRepository $repository
     * @param int $page
     * @return JsonResponse
     */
    public function list(ProductRepository $repository,int $page): JsonResponse
    {
        $show=$this->getParameter('products_per_page');
        $products=$repository->getProductsByPage($page,$show);

        return $this->json($products);

    }

    /**
     * @Route("/api/create_cart", name="create_cart", methods={"POST"})
     * @param CartRepository $repository
     * @return JsonResponse
     */
    public function createCart(CartRepository $repository): JsonResponse
    {
        /**
         * @var $user User
         */
        $user=$this->getUser();
        $cart=$repository->getActiveUserCart($user);

        if(is_null($cart))
        {
            $cart =new Cart();
            $cart->setUser($user);
            $em=$this->getDoctrine()->getManager();
            $em->persist($cart);
            $em->flush();
            return new JsonResponse(array('status'=>'Cart created'),201);
        }else{
            return new JsonResponse(array('status'=>'Cart already exist'),200);
        }
    }

    /**
     * @Route("/api/my_cart", name="my_cart", methods={"GET"})
     * @param CartRepository $repository
     * @return JsonResponse
     */
    public function myCart(CartRepository $repository)
    {
        /** @var User $user */
        $user=$this->getUser();
        /** @var Cart|null $cart */
        $cart=$repository->getActiveUserCart($user);
        if(is_null($cart))
        {
            return new JsonResponse(array('status'=>'error','message'=>"Cart dont exist"),400);
        }

        if($cart->getItems()===0)
        {
            return $this->json(array('status'=>'info','message'=>'Cart is empty','cart_info'=>$this->getCartSummary($cart)));
        }

        return $this->json(array('status'=>'info','cart_info'=>$this->getCartSummary($cart),'products'=>$this->getCartProducts($cart)));
    }

    /**
     * @Route("/api/add_to_cart", name="add_to_cart", methods={"POST"})
     * @param Request $request
     * @param CartRepository $repository
     * @param ProductRepository $productRepository
     * @param CartItemRepository $cartItemRepository
     * @return JsonResponse
     */
    public function addToCart(Request $request,CartRepository $repository,ProductRepository $productRepository,CartItemRepository $cartItemRepository)
    {
        if ($request->getContentType() != 'json' || !$request->getContent()) {
            return $this->json(array('message'=>'Request must be json type'),400);
        }
        /** @var User $user */
        $user=$this->getUser();
        /** @var Cart|null $cart */
        $cart=$repository->getActiveUserCart($user);
        if(is_null($cart))
        {
            return $this->json(array('status'=>'error','message'=>"Cart dont exist"),400);
        }
        $data = json_decode($request->getContent(), true);
        $product=$productRepository->find($data['product']);
        if(is_null($product))
        {
            return $this->json(array('status'=>'error','message'=>"Product dont exist"),400);
        }
        // sprawdzam ilość pozycji w koszyku
        if($cart->getItems()==3)
        {
            return $this->json(array('status'=>'info','message'=>'Cart is full','cart_info'=>$this->getCartSummary($cart)));
        }
        // sprawdzam czy produkt jest juz w koszyku
        /** @var CartItem|null $item */
        $item=$cartItemRepository->getCartItemWithProduct($cart,$product);
        $em=$this->getDoctrine()->getManager();
        if(is_null($item))
        {
            $item=new CartItem($product);
            $cart->addCartItem($item);
            $cart->setItems($cart->getItems()+1);
            $em->persist($item);
            $em->persist($cart);
            $em->flush();

            return $this->json(array('status'=>'product added','cart_info'=>$this->getCartSummary($cart)));
        }
        return $this->json(array('status'=>'product already in cart','cart_info'=>$this->getCartSummary($cart)));
    }

    /**
     * @Route("/api/remove_from_cart", name="remove_from_cart", methods={"DELETE"})
     * @param Request $request
     * @param CartRepository $repository
     * @param ProductRepository $productRepository
     * @param CartItemRepository $cartItemRepository
     * @return JsonResponse
     */
    public function removeFromCart(Request $request,CartRepository $repository,ProductRepository $productRepository,CartItemRepository $cartItemRepository)
    {
        if ($request->getContentType() != 'json' || !$request->getContent()) {
            return $this->json(array('message'=>'Request must be json type'),400);
        }
        /** @var User $user */
        $user=$this->getUser();
        /** @var Cart|null $cart */
        $cart=$repository->getActiveUserCart($user);
        if(is_null($cart))
        {
            return $this->json(array('status'=>'error','message'=>"Cart dont exist"),400);
        }
        $data = json_decode($request->getContent(), true);
        $product=$productRepository->find($data['product']);
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
        $item=$cartItemRepository->getCartItemWithProduct($cart,$product);
        $em=$this->getDoctrine()->getManager();
        if(is_null($item))
        {
            return $this->json(array('status'=>'error','message'=>'Product is not in the cart'));
        }
        $em->remove($item);
        $cart->setItems($cart->getItems()-1);
        $em->flush();
        return $this->json(array('status'=>'Product removed from cart','cart_info'=>$this->getCartSummary($cart)));
    }

    private function getCartSummary(Cart $cart)
    {
        $total=0;
        foreach ($cart->getCartItems() as $item)
        {
            $total+=$item->getProduct()->getPrice();
        }
        return array('items'=>$cart->getItems(),'total'=>round($total,2));
    }

    private function getCartProducts(Cart $cart)
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
