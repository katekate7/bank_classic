<?php

namespace App\Tests\Integration;

use App\Entity\User;
use App\Entity\Category;
use App\Entity\Expense;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Tests d'intégration complets : Frontend → Backend → Database
 * Vérifie que toutes les parties de l'application fonctionnent ensemble
 */
class CompleteIntegrationTest extends WebTestCase
{
    private $client;
    private ?EntityManagerInterface $entityManager = null;
    private ?UserPasswordHasherInterface $passwordHasher = null;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManager();
        $this->passwordHasher = $this->client->getContainer()->get(UserPasswordHasherInterface::class);
    }

    /**
     * Test d'intégration complet : Inscription → Connexion → Ajout de dépense
     * Simule un parcours utilisateur complet depuis l'inscription jusqu'à l'ajout d'une dépense
     */
    public function testCompleteUserJourney(): void
    {
        // 1. Test d'inscription via API
        $this->client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'newuser@example.com',
            'password' => 'password123'
        ]));

        $this->assertEquals(201, $this->client->getResponse()->getStatusCode());

        // 2. Vérifier que l'utilisateur est créé en base
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'newuser@example.com']);
        $this->assertNotNull($user, 'L\'utilisateur doit être créé en base de données');

        // 3. Test de connexion
        $this->client->request('POST', '/login', [
            'email' => 'newuser@example.com',
            'password' => 'password123'
        ]);

        $this->assertTrue($this->client->getResponse()->isRedirection());

        // 4. Créer une catégorie pour les tests
        $category = new Category();
        $category->setName('Food');
        $this->entityManager->persist($category);
        $this->entityManager->flush();

        // 5. Test d'ajout de dépense via API (simulant le frontend)
        $this->client->loginUser($user);
        $this->client->request('POST', '/api/expense', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'label' => 'Lunch at restaurant',
            'amount' => 25.50,
            'date' => '2024-01-15',
            'category' => 'Food'
        ]));

        $this->assertEquals(201, $this->client->getResponse()->getStatusCode());

        // 6. Vérifier que la dépense est créée en base
        $expense = $this->entityManager->getRepository(Expense::class)->findOneBy([
            'label' => 'Lunch at restaurant',
            'user' => $user
        ]);

        $this->assertNotNull($expense, 'La dépense doit être créée en base');
        $this->assertEquals(25.50, $expense->getAmount());
        $this->assertEquals('Food', $expense->getCategory()->getName());
    }

    /**
     * Test d'intégration : CRUD complet sur les dépenses
     */
    public function testExpenseCRUDIntegration(): void
    {
        // Setup : Créer utilisateur et catégorie
        $user = new User();
        $user->setEmail('crud-test@example.com');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));

        $category = new Category();
        $category->setName('Transportation');

        $this->entityManager->persist($user);
        $this->entityManager->persist($category);
        $this->entityManager->flush();

        $this->client->loginUser($user);

        // 1. CREATE - Ajouter une dépense
        $this->client->request('POST', '/api/expense', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'label' => 'Bus ticket',
            'amount' => 2.50,
            'date' => '2024-01-15',
            'category' => 'Transportation'
        ]));

        $this->assertEquals(201, $this->client->getResponse()->getStatusCode());
        $createResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expenseId = $createResponse['expense_id'];

        // 2. READ - Récupérer toutes les dépenses
        $this->client->request('GET', '/api/expenses');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        
        $expenses = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(1, $expenses);
        $this->assertEquals('Bus ticket', $expenses[0]['label']);

        // 3. READ - Récupérer une dépense spécifique
        $this->client->request('GET', "/api/expense/{$expenseId}");
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        
        $expense = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Bus ticket', $expense['label']);

        // 4. UPDATE - Modifier la dépense
        $this->client->request('PUT', "/api/expense/{$expenseId}", [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'label' => 'Metro ticket',
            'amount' => 3.00
        ]));

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        // Vérifier la modification en base
        $updatedExpense = $this->entityManager->getRepository(Expense::class)->find($expenseId);
        $this->assertEquals('Metro ticket', $updatedExpense->getLabel());
        $this->assertEquals(3.00, $updatedExpense->getAmount());

        // 5. DELETE - Supprimer la dépense
        $this->client->request('DELETE', "/api/expense/{$expenseId}");
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        // Vérifier la suppression en base
        $deletedExpense = $this->entityManager->getRepository(Expense::class)->find($expenseId);
        $this->assertNull($deletedExpense);
    }

    /**
     * Test d'intégration : Sécurité et autorisation
     */
    public function testSecurityIntegration(): void
    {
        // Créer deux utilisateurs
        $user1 = new User();
        $user1->setEmail('user1@example.com');
        $user1->setPassword($this->passwordHasher->hashPassword($user1, 'password123'));

        $user2 = new User();
        $user2->setEmail('user2@example.com');
        $user2->setPassword($this->passwordHasher->hashPassword($user2, 'password123'));

        $category = new Category();
        $category->setName('Personal');

        $this->entityManager->persist($user1);
        $this->entityManager->persist($user2);
        $this->entityManager->persist($category);
        $this->entityManager->flush();

        // Créer une dépense pour user1
        $expense = new Expense();
        $expense->setLabel('Private expense');
        $expense->setAmount(100.00);
        $expense->setDate(new \DateTimeImmutable());
        $expense->setCategory($category);
        $expense->setUser($user1);

        $this->entityManager->persist($expense);
        $this->entityManager->flush();

        // Test 1: user2 ne doit pas pouvoir accéder à la dépense de user1
        $this->client->loginUser($user2);
        $this->client->request('GET', "/api/expense/{$expense->getId()}");
        $this->assertEquals(403, $this->client->getResponse()->getStatusCode());

        // Test 2: user2 ne doit pas pouvoir modifier la dépense de user1
        $this->client->request('PUT', "/api/expense/{$expense->getId()}", [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['label' => 'Hacked expense']));
        $this->assertEquals(403, $this->client->getResponse()->getStatusCode());

        // Test 3: user1 doit pouvoir accéder à sa propre dépense
        $this->client->loginUser($user1);
        $this->client->request('GET', "/api/expense/{$expense->getId()}");
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    }

    /**
     * Test d'intégration : Gestion des erreurs
     */
    public function testErrorHandlingIntegration(): void
    {
        $user = new User();
        $user->setEmail('error-test@example.com');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->loginUser($user);

        // Test 1: Données invalides pour création de dépense
        $this->client->request('POST', '/api/expense', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'label' => '', // Label vide
            'amount' => -10, // Montant négatif
            'date' => 'invalid-date',
            'category' => 'NonExistentCategory'
        ]));

        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());

        // Test 2: Accès à une dépense inexistante
        $this->client->request('GET', '/api/expense/99999');
        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());

        // Test 3: JSON malformé
        $this->client->request('POST', '/api/expense', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], 'invalid json');

        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Nettoyer la base de données
        $this->entityManager->createQuery('DELETE FROM App\\Entity\\Expense')->execute();
        $this->entityManager->createQuery('DELETE FROM App\\Entity\\Category')->execute();
        $this->entityManager->createQuery('DELETE FROM App\\Entity\\User')->execute();
        
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
