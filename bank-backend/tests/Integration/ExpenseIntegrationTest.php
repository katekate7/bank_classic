<?php

namespace App\Tests\Integration;

use App\Entity\User;
use App\Entity\Category;
use App\Entity\Expense;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Test d'intégration pour vérifier que l'application fonctionne correctement
 * dans son ensemble : interaction entre frontend, backend et base de données
 * 
 * Exemple requis : "Tester qu'une nouvelle dépense peut être ajoutée via le frontend,
 * envoyée au backend, et stockée dans la base de données"
 */
class ExpenseIntegrationTest extends WebTestCase
{
    private $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManager();
    }

    /**
     * Test d'intégration complet : Frontend → Backend → Database
     * Simule l'ajout d'une nouvelle dépense via l'API (comme le ferait le frontend)
     * et vérifie qu'elle est bien stockée en base de données
     */
    public function testAddExpenseFromFrontendToDatabase(): void
    {
        // 1. Créer un utilisateur et une catégorie pour le test
        $user = new User();
        $user->setEmail('integration-test@example.com');
        $user->setFirstName('Integration');
        $user->setLastName('Test');
        
        $passwordHasher = $this->client->getContainer()->get(UserPasswordHasherInterface::class);
        $user->setPassword($passwordHasher->hashPassword($user, 'password123'));

        $category = new Category();
        $category->setName('Test Category');
        $category->setDescription('Category for integration testing');
        $category->setColor('#FF5733');

        $this->entityManager->persist($user);
        $this->entityManager->persist($category);
        $this->entityManager->flush();

        // 2. Simuler l'authentification (comme le frontend)
        $this->client->loginUser($user);

        // 3. Envoyer une requête POST pour créer une dépense (comme le frontend)
        $expenseData = [
            'amount' => 42.50,
            'description' => 'Test expense from integration test',
            'category' => $category->getId(),
            'date' => '2024-01-15'
        ];

        $this->client->request(
            'POST',
            '/api/expenses',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($expenseData)
        );

        // 4. Vérifier que la réponse du backend est correcte
        $this->assertResponseStatusCodeSame(201, 'Le backend doit confirmer la création de la dépense');
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $responseData, 'La réponse doit contenir l\'ID de la dépense créée');
        $this->assertEquals(42.50, $responseData['amount'], 'Le montant doit être correct');

        // 5. CRUCIAL : Vérifier que la dépense est réellement stockée en base de données
        $expenseId = $responseData['id'];
        $storedExpense = $this->entityManager->getRepository(Expense::class)->find($expenseId);
        
        $this->assertNotNull($storedExpense, 'La dépense doit être persistée en base de données');
        $this->assertEquals(42.50, $storedExpense->getAmount(), 'Le montant en base doit correspondre');
        $this->assertEquals('Test expense from integration test', $storedExpense->getDescription());
        $this->assertEquals($user->getId(), $storedExpense->getUser()->getId(), 'La dépense doit être liée au bon utilisateur');
        $this->assertEquals($category->getId(), $storedExpense->getCategory()->getId(), 'La dépense doit être liée à la bonne catégorie');
        
        // 6. Vérifier que la dépense peut être récupérée via l'API (comme le ferait le frontend)
        $this->client->request('GET', '/api/expenses');
        $this->assertResponseIsSuccessful('La récupération des dépenses doit fonctionner');
        
        $expensesData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($expensesData, 'La réponse doit être un tableau');
        
        // Chercher notre dépense dans la liste
        $foundExpense = null;
        foreach ($expensesData as $expense) {
            if ($expense['id'] === $expenseId) {
                $foundExpense = $expense;
                break;
            }
        }
        
        $this->assertNotNull($foundExpense, 'La dépense créée doit apparaître dans la liste des dépenses');
        $this->assertEquals(42.50, $foundExpense['amount'], 'Les données récupérées doivent être correctes');
    }

    /**
     * Test de la chaîne complète : Frontend → Backend → Database → Frontend
     * Vérifie les opérations CRUD complètes
     */
    public function testCompleteExpenseCRUDCycle(): void
    {
        // Setup
        $user = new User();
        $user->setEmail('crud-test@example.com');
        $user->setFirstName('CRUD');
        $user->setLastName('Test');
        
        $passwordHasher = $this->client->getContainer()->get(UserPasswordHasherInterface::class);
        $user->setPassword($passwordHasher->hashPassword($user, 'password123'));

        $category = new Category();
        $category->setName('CRUD Category');
        $category->setDescription('Category for CRUD testing');
        $category->setColor('#3498DB');

        $this->entityManager->persist($user);
        $this->entityManager->persist($category);
        $this->entityManager->flush();

        $this->client->loginUser($user);

        // 1. CREATE - Ajouter une dépense
        $createData = [
            'amount' => 30.00,
            'description' => 'CRUD Test Expense',
            'category' => $category->getId()
        ];

        $this->client->request('POST', '/api/expenses', [], [], 
            ['CONTENT_TYPE' => 'application/json'], json_encode($createData));
        
        $this->assertResponseStatusCodeSame(201);
        $createdExpense = json_decode($this->client->getResponse()->getContent(), true);
        $expenseId = $createdExpense['id'];

        // 2. READ - Lire la dépense
        $this->client->request('GET', "/api/expenses/{$expenseId}");
        $this->assertResponseIsSuccessful();
        $readExpense = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(30.00, $readExpense['amount']);

        // 3. UPDATE - Modifier la dépense
        $updateData = [
            'amount' => 35.00,
            'description' => 'CRUD Test Expense - Updated'
        ];

        $this->client->request('PUT', "/api/expenses/{$expenseId}", [], [], 
            ['CONTENT_TYPE' => 'application/json'], json_encode($updateData));
        
        $this->assertResponseIsSuccessful();

        // Vérifier en base que la modification a été persistée
        $this->entityManager->clear(); // Force refresh from database
        $updatedExpense = $this->entityManager->getRepository(Expense::class)->find($expenseId);
        $this->assertEquals(35.00, $updatedExpense->getAmount());
        $this->assertEquals('CRUD Test Expense - Updated', $updatedExpense->getDescription());

        // 4. DELETE - Supprimer la dépense
        $this->client->request('DELETE', "/api/expenses/{$expenseId}");
        $this->assertResponseStatusCodeSame(204);

        // Vérifier en base que la suppression a été effectuée
        $this->entityManager->clear();
        $deletedExpense = $this->entityManager->getRepository(Expense::class)->find($expenseId);
        $this->assertNull($deletedExpense, 'La dépense doit être supprimée de la base de données');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
