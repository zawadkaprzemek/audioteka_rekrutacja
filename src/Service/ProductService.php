<?php

namespace App\Service;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;

class ProductService
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getProduct(int $prodId)
    {
        return $this->entityManager->getRepository(Product::class)->find($prodId);
    }

    /**
     * @param int $page
     * @param int $show
     * @return Product[]
     */
    public function getProductsByPage(int $page,int $show): array
    {
        return $this->entityManager->getRepository(Product::class)->getProductsByPage($page,$show);
    }

    public function deleteProduct(Product $product):void
    {
        $this->entityManager->remove($product);
        $this->entityManager->flush();
    }
}