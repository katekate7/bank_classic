<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiLoginController extends AbstractController
{
    #[Route('/api/login', name: 'api_user_login', methods: ['POST'])]
    public function login(): Response
    {
        // This method will be intercepted by the authenticator
        // If we reach here, authentication was successful
        return $this->json(['message' => '✅ Login successful']);
    }
}
