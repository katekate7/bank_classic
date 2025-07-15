<?php

namespace App\Tests\Integration;

use App\Entity\User;
use App\Entity\Category;
use App\Entity\Expense;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Test complet d'intégration frontend-backend-database
 * Simule le parcours utilisateur complet : ajout d'une dépense via le frontend,
 * envoi au backend, et stockage en base de données
 */
class FullStackIntegrationTest extends WebTestCase
{
    private $client;
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        
        $this->entityManager = $this->client->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->passwordHasher = $this->client->getContainer()
            ->get(UserPasswordHasherInterface::class);
    }

    /**
     * Test d'intégration complet : Ajouter une nouvelle dépense
     * 
     * Scénario testé :
     * 1. Un utilisateur se connecte
     * 2. Il ajoute une nouvelle dépense via l'API (simulant le frontend)
     * 3. La dépense est stockée en base de données
     * 4. La dépense peut être récupérée via l'API
     */
    public function testCompleteExpenseCreationFlow(): void
    {
        // 🏗️ PHASE 1: Préparation des données de test
        $user = $this->createTestUser('fullstack@example.com', 'FullStack', 'User');
        $category = $this->createTestCategory('Integration Test', 'Test category for full integration', '#FF6B6B');
        
        $this->entityManager->persist($user);
        $this->entityManager->persist($category);
        $this->entityManager->flush();

        // 🔐 PHASE 2: Authentification utilisateur
        $this->client->loginUser($user);

        // 📝 PHASE 3: Création d'une dépense via API (simule l'action frontend)
        $expenseData = [
            'amount' => 89.99,
            'description' => 'Test expense - Restaurant dinner',
            'category' => $category->getId(),
            'date' => '2024-01-20'
        ];

        $this->client->request(
            'POST',
            '/api/expenses',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($expenseData)
        );

        // ✅ VÉRIFICATION 1: Réponse API correcte
        $this->assertResponseStatusCodeSame(201, 'La création de dépense doit retourner un code 201');
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $responseData, 'La réponse doit contenir un ID');
        $this->assertEquals(89.99, $responseData['amount'], 'Le montant doit être correct');
        $this->assertEquals('Test expense - Restaurant dinner', $responseData['description']);

        $expenseId = $responseData['id'];

        // ✅ VÉRIFICATION 2: Données correctement stockées en base
        $storedExpense = $this->entityManager->getRepository(Expense::class)->find($expenseId);
        $this->assertNotNull($storedExpense, 'La dépense doit être stockée en base de données');
        $this->assertEquals(89.99, $storedExpense->getAmount());
        $this->assertEquals('Test expense - Restaurant dinner', $storedExpense->getDescription());
        $this->assertEquals($user->getId(), $storedExpense->getUser()->getId());
        $this->assertEquals($category->getId(), $storedExpense->getCategory()->getId());

        // 📊 PHASE 4: Récupération via API (simule l'affichage frontend)
        $this->client->request('GET', '/api/expenses');
        
        $this->assertResponseIsSuccessful('La récupération des dépenses doit fonctionner');
        $expensesData = json_decode($this->client->getResponse()->getContent(), true);
        
        // Chercher notre dépense dans la liste
        $foundExpense = null;
        foreach ($expensesData['data'] ?? $expensesData as $expense) {
            if ($expense['id'] === $expenseId) {
                $foundExpense = $expense;
                break;
            }
        }

        $this->assertNotNull($foundExpense, 'La dépense créée doit apparaître dans la liste');
        $this->assertEquals(89.99, $foundExpense['amount']);
    }

    /**
     * Test du parcours complet de mise à jour d'une dépense
     */
    public function testCompleteExpenseUpdateFlow(): void
    {
        // Préparation
        $user = $this->createTestUser('update-flow@example.com', 'Update', 'Flow');
        $originalCategory = $this->createTestCategory('Original', 'Original category', '#4ECDC4');
        $newCategory = $this->createTestCategory('Updated', 'Updated category', '#45B7D1');
        
        $expense = new Expense();
        $expense->setAmount(45.00);
        $expense->setDescription('Original expense');
        $expense->setUser($user);
        $expense->setCategory($originalCategory);
        $expense->setCreatedAt(new \DateTime());

        $this->entityManager->persist($user);
        $this->entityManager->persist($originalCategory);
        $this->entityManager->persist($newCategory);
        $this->entityManager->persist($expense);
        $this->entityManager->flush();

        $originalExpenseId = $expense->getId();

        // Authentification
        $this->client->loginUser($user);

        // Mise à jour via API
        $updateData = [
            'amount' => 67.50,
            'description' => 'Updated expense description',
            'category' => $newCategory->getId()
        ];

        $this->client->request(
            'PUT',
            '/api/expenses/' . $originalExpenseId,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($updateData)
        );

        $this->assertResponseIsSuccessful('La mise à jour doit réussir');

        // Vérification en base
        $this->entityManager->refresh($expense);
        $this->assertEquals(67.50, $expense->getAmount());
        $this->assertEquals('Updated expense description', $expense->getDescription());
        $this->assertEquals($newCategory->getId(), $expense->getCategory()->getId());

        // Vérification via API
        $this->client->request('GET', '/api/expenses/' . $originalExpenseId);
        $this->assertResponseIsSuccessful();
        
        $updatedExpenseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(67.50, $updatedExpenseData['amount']);
        $this->assertEquals('Updated expense description', $updatedExpenseData['description']);
    }

    /**
     * Test du parcours de suppression d'une dépense
     */
    public function testCompleteExpenseDeleteFlow(): void
    {
        // Préparation
        $user = $this->createTestUser('delete-flow@example.com', 'Delete', 'Flow');
        $category = $this->createTestCategory('Delete Test', 'Category for deletion', '#E74C3C');
        
        $expense = new Expense();
        $expense->setAmount(30.00);
        $expense->setDescription('Expense to be deleted');
        $expense->setUser($user);
        $expense->setCategory($category);
        $expense->setCreatedAt(new \DateTime());

        $this->entityManager->persist($user);
        $this->entityManager->persist($category);
        $this->entityManager->persist($expense);
        $this->entityManager->flush();

        $expenseIdToDelete = $expense->getId();

        // Authentification
        $this->client->loginUser($user);

        // Vérifier que la dépense existe avant suppression
        $this->client->request('GET', '/api/expenses/' . $expenseIdToDelete);
        $this->assertResponseIsSuccessful('La dépense doit exister avant suppression');

        // Suppression via API
        $this->client->request('DELETE', '/api/expenses/' . $expenseIdToDelete);
        $this->assertResponseStatusCodeSame(204, 'La suppression doit retourner 204');

        // Vérification en base
        $deletedExpense = $this->entityManager->getRepository(Expense::class)->find($expenseIdToDelete);
        $this->assertNull($deletedExpense, 'La dépense doit être supprimée de la base');

        // Vérification via API
        $this->client->request('GET', '/api/expenses/' . $expenseIdToDelete);
        $this->assertResponseStatusCodeSame(404, 'La dépense supprimée ne doit plus être accessible');
    }

    /**
     * Test du parcours complet de gestion des catégories
     */
    public function testCompleteCategoryManagementFlow(): void
    {
        // Préparation
        $user = $this->createTestUser('category-flow@example.com', 'Category', 'Manager');
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Authentification
        $this->client->loginUser($user);

        // 1. Récupération de la liste initiale des catégories
        $this->client->request('GET', '/api/categories');
        $this->assertResponseIsSuccessful();
        $initialCategories = json_decode($this->client->getResponse()->getContent(), true);
        $initialCount = count($initialCategories);

        // 2. Création d'une nouvelle catégorie
        $categoryData = [
            'name' => 'Travel & Transport',
            'description' => 'All travel related expenses',
            'color' => '#9B59B6'
        ];

        $this->client->request(
            'POST',
            '/api/categories',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($categoryData)
        );

        $this->assertResponseStatusCodeSame(201);
        $newCategoryData = json_decode($this->client->getResponse()->getContent(), true);
        $newCategoryId = $newCategoryData['id'];

        // 3. Vérification que la catégorie est en base
        $storedCategory = $this->entityManager->getRepository(Category::class)->find($newCategoryId);
        $this->assertNotNull($storedCategory);
        $this->assertEquals('Travel & Transport', $storedCategory->getName());

        // 4. Vérification via liste API
        $this->client->request('GET', '/api/categories');
        $updatedCategories = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount($initialCount + 1, $updatedCategories);

        // 5. Création d'une dépense avec cette catégorie
        $expenseData = [
            'amount' => 125.00,
            'description' => 'Flight ticket Paris-London',
            'category' => $newCategoryId,
            'date' => '2024-02-01'
        ];

        $this->client->request(
            'POST',
            '/api/expenses',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($expenseData)
        );

        $this->assertResponseStatusCodeSame(201);
        $expenseResponse = json_decode($this->client->getResponse()->getContent(), true);

        // 6. Vérification du lien dépense-catégorie en base
        $createdExpense = $this->entityManager->getRepository(Expense::class)->find($expenseResponse['id']);
        $this->assertEquals($newCategoryId, $createdExpense->getCategory()->getId());
        $this->assertEquals('Travel & Transport', $createdExpense->getCategory()->getName());
    }

    /**
     * Test de la gestion des erreurs dans le flux complet
     */
    public function testErrorHandlingInCompleteFlow(): void
    {
        $user = $this->createTestUser('error-test@example.com', 'Error', 'Handler');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->loginUser($user);

        // Test 1: Création de dépense avec catégorie inexistante
        $invalidExpenseData = [
            'amount' => 50.00,
            'description' => 'Test with invalid category',
            'category' => 99999
        ];

        $this->client->request(
            'POST',
            '/api/expenses',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($invalidExpenseData)
        );

        $this->assertResponseStatusCodeSame(400, 'Doit retourner une erreur pour catégorie invalide');

        // Test 2: Tentative de mise à jour d'une dépense inexistante
        $this->client->request(
            'PUT',
            '/api/expenses/99999',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['amount' => 100])
        );

        $this->assertResponseStatusCodeSame(404, 'Doit retourner 404 pour dépense inexistante');

        // Test 3: Validation des données d'entrée
        $invalidData = [
            'amount' => -50,  // Montant négatif
            'description' => '',  // Description vide
        ];

        $this->client->request(
            'POST',
            '/api/expenses',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($invalidData)
        );

        $this->assertResponseStatusCodeSame(400, 'Doit rejeter les données invalides');
    }

    private function createTestUser(string $email, string $firstName, string $lastName): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));
        
        return $user;
    }

    private function createTestCategory(string $name, string $description, string $color): Category
    {
        $category = new Category();
        $category->setName($name);
        $category->setDescription($description);
        $category->setColor($color);
        
        return $category;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
