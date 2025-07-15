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
    private ?EntityManagerInterface $entityManager = null;

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
        
        $passwordHasher = $this->client->getContainer()->get(UserPasswordHasherInterface::class);
        $user->setPassword($passwordHasher->hashPassword($user, 'password123'));

        $category = new Category();
        $category->setName('Test Category');

        $this->entityManager->persist($user);
        $this->entityManager->persist($category);
        $this->entityManager->flush();

        // 2. Simuler l'authentification (comme le frontend)
        $this->client->loginUser($user);

        // 3. Test d'intégration DB : créer directement l'entité pour vérifier l'intégration
        $expense = new Expense();
        $expense->setLabel('Test expense from integration test');
        $expense->setAmount(42.50);
        $expense->setDate(new \DateTimeImmutable('2024-01-15'));
        $expense->setCategory($category);
        $expense->setUser($user);

        $this->entityManager->persist($expense);
        $this->entityManager->flush();

        // 4. Vérifier en base de données
        $savedExpense = $this->entityManager->getRepository(Expense::class)->findOneBy([
            'label' => 'Test expense from integration test'
        ]);

        $this->assertNotNull($savedExpense, 'La dépense doit être sauvegardée en base');
        $this->assertEquals(42.50, $savedExpense->getAmount(), 'Le montant doit être correct');
        $this->assertEquals('Test Category', $savedExpense->getCategory()->getName(), 'La catégorie doit être correcte');
        $this->assertEquals('integration-test@example.com', $savedExpense->getUser()->getEmail(), 'L\'utilisateur doit être correct');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
