<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\CategoryRepository;

#[Route('/api')]
class CategoryController extends AbstractController
{
    #[Route('/categories', name: 'categories_list', methods: ['GET'])]
    public function list(CategoryRepository $categoryRepository): JsonResponse
    {
        $categories = $categoryRepository->findAll();
        $categoryNames = array_map(fn($category) => $category->getName(), $categories);
        return $this->json($categoryNames);
    }
}
