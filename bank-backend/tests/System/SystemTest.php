<?php

namespace App\Tests\System;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests système pour vérifier le bon fonctionnement global de l'application
 * Ces tests vérifient l'application dans son ensemble, incluant la configuration,
 * les services, et l'infrastructure
 */
class SystemTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * Test de santé de l'application - Vérifie que tous les services fonctionnent
     */
    public function testApplicationHealth(): void
    {
        $this->client->request('GET', '/api/health');
        
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        
        $healthData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('status', $healthData);
        $this->assertEquals('healthy', $healthData['status']);
        $this->assertArrayHasKey('timestamp', $healthData);
        $this->assertArrayHasKey('database', $healthData);
        $this->assertEquals('connected', $healthData['database']);
    }

    /**
     * Test de performance - Vérifie que l'API répond dans un délai acceptable
     */
    public function testApiPerformance(): void
    {
        $startTime = microtime(true);
        
        $this->client->request('GET', '/api/categories');
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000; // en millisecondes
        
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertLessThan(1000, $responseTime, 'L\'API doit répondre en moins de 1 seconde');
    }

    /**
     * Test de charge basique - Vérifie que l'application gère plusieurs requêtes simultanées
     */
    public function testBasicLoadHandling(): void
    {
        $responses = [];
        
        // Simuler plusieurs requêtes simultanées
        for ($i = 0; $i < 10; $i++) {
            $client = static::createClient();
            $client->request('GET', '/api/categories');
            $responses[] = $client->getResponse()->getStatusCode();
        }
        
        // Vérifier que toutes les requêtes ont réussi
        foreach ($responses as $statusCode) {
            $this->assertEquals(200, $statusCode);
        }
    }

    /**
     * Test de sécurité - Vérifie les en-têtes de sécurité
     */
    public function testSecurityHeaders(): void
    {
        $this->client->request('GET', '/');
        
        $response = $this->client->getResponse();
        
        // Vérifier les en-têtes de sécurité
        $this->assertTrue($response->headers->has('X-Content-Type-Options'));
        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
        
        $this->assertTrue($response->headers->has('X-Frame-Options'));
        $this->assertContains($response->headers->get('X-Frame-Options'), ['DENY', 'SAMEORIGIN']);
    }

    /**
     * Test de configuration de l'environnement
     */
    public function testEnvironmentConfiguration(): void
    {
        $container = $this->client->getContainer();
        
        // Vérifier que nous sommes en environnement de test
        $this->assertEquals('test', $container->getParameter('kernel.environment'));
        
        // Vérifier que les services critiques sont disponibles
        $this->assertTrue($container->has('doctrine.orm.entity_manager'));
        $this->assertTrue($container->has('security.password_hasher'));
        
        // Vérifier la configuration de la base de données
        $em = $container->get('doctrine.orm.entity_manager');
        $this->assertNotNull($em);
        
        // Test de connexion à la base de données
        $connection = $em->getConnection();
        $this->assertTrue($connection->isConnected() || $connection->connect());
    }

    /**
     * Test des routes principales
     */
    public function testMainRoutes(): void
    {
        $routes = [
            '/' => 200,
            '/api/categories' => 200,
            '/api/health' => 200,
            '/login' => 200,
            '/register' => 200,
        ];

        foreach ($routes as $route => $expectedStatus) {
            $this->client->request('GET', $route);
            $this->assertEquals(
                $expectedStatus, 
                $this->client->getResponse()->getStatusCode(),
                "La route {$route} doit retourner le statut {$expectedStatus}"
            );
        }
    }

    /**
     * Test de gestion des erreurs 404
     */
    public function testNotFoundHandling(): void
    {
        $this->client->request('GET', '/non-existent-route');
        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());
    }

    /**
     * Test de l'API CORS pour le frontend
     */
    public function testCORSConfiguration(): void
    {
        $this->client->request('OPTIONS', '/api/categories', [], [], [
            'HTTP_ORIGIN' => 'http://localhost:3000',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'content-type'
        ]);

        $response = $this->client->getResponse();
        
        // Vérifier que les en-têtes CORS sont présents
        $this->assertTrue($response->headers->has('Access-Control-Allow-Origin'));
        $this->assertTrue($response->headers->has('Access-Control-Allow-Methods'));
    }

    /**
     * Test de validation des données d'entrée
     */
    public function testInputValidation(): void
    {
        // Test avec des données malveillantes (XSS)
        $this->client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => '<script>alert("xss")</script>@test.com',
            'password' => 'password123'
        ]));

        // Doit retourner une erreur de validation
        $this->assertNotEquals(201, $this->client->getResponse()->getStatusCode());
    }

    /**
     * Test de monitoring et logs
     */
    public function testLoggingSystem(): void
    {
        $container = $this->client->getContainer();
        
        // Vérifier que le système de logs est configuré
        $this->assertTrue($container->has('logger'));
        
        $logger = $container->get('logger');
        $this->assertNotNull($logger);
    }
}
