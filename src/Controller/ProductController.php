<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Service\ProductService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;


/**
 * @Route("/api/products/")
 */
class ProductController extends AbstractController
{

    /**
     * @var ProductService
     */
    private $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * @Route("/add", name="add_product", methods={"POST"})
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
     * @Route("/{id}/delete", name="delete_product", methods={"DELETE"})
     * @param Product $product
     * @return JsonResponse
     */
    public function deleteAction(Product $product): JsonResponse
    {
        $this->productService->deleteProduct($product);
        return new JsonResponse(array('status'=>'deleted'),200);
    }


    /**
     * @Route("/{id}/edit/{type}",name="edit_product", methods={"PATCH"},defaults={"type": "name"},requirements={"type"="name|price"})
     * @param Product $product
     * @param Request $request
     * @param string $type
     * @return JsonResponse
     */
    public function editAction(Product $product,Request $request,string $type): JsonResponse
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
     * @Route("{page}", name="products_list", defaults={"page":1}, requirements={"page"="\d+"}, methods={"GET"})
     * @param int $page
     * @return JsonResponse
     */
    public function list(int $page): JsonResponse
    {
        $show=$this->getParameter('products_per_page');
        $products=$this->productService->getProductsByPage($page,$show);

        return $this->json($products);

    }

    private function getErrorsFromForm(FormInterface $form): array
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

}
