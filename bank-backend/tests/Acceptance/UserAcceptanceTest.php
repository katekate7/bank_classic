<?php

namespace App\Tests\Acceptance;

use App\Entity\User;
use App\Entity\Category;
use App\Entity\Expense;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Tests d'acceptation client
 * Ces tests vérifient que l'application répond aux exigences fonctionnelles
 * du point de vue de l'utilisateur final
 */
class UserAcceptanceTest extends WebTestCase
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
     * Scénario d'acceptation : Un nouvel utilisateur peut s'inscrire et gérer ses dépenses
     */
    public function testNewUserCanRegisterAndManageExpenses(): void
    {
        // ÉTANT DONNÉ qu'un nouvel utilisateur souhaite utiliser l'application
        
        // QUAND il s'inscrit avec son email et mot de passe
        $this->client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'nouveau-client@bank.com',
            'password' => 'motdepasse123'
        ]));

        // ALORS son compte est créé avec succès
        $this->assertEquals(201, $this->client->getResponse()->getStatusCode());
        
        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => 'nouveau-client@bank.com']);
        $this->assertNotNull($user, 'Le compte utilisateur doit être créé');

        // ET il peut se connecter à l'application
        $this->client->request('POST', '/login', [
            'email' => 'nouveau-client@bank.com',
            'password' => 'motdepasse123'
        ]);
        $this->assertTrue($this->client->getResponse()->isRedirection());

        // ET il peut consulter ses dépenses (liste vide au début)
        $this->client->loginUser($user);
        $this->client->request('GET', '/api/expenses');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        
        $expenses = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEmpty($expenses, 'La liste des dépenses doit être vide pour un nouvel utilisateur');
    }

    /**
     * Scénario d'acceptation : Un utilisateur peut ajouter et catégoriser ses dépenses
     */
    public function testUserCanAddAndCategorizeExpenses(): void
    {
        // ÉTANT DONNÉ qu'un utilisateur est connecté
        $user = $this->createTestUser('client-depenses@bank.com');
        $this->client->loginUser($user);

        // ET que des catégories sont disponibles
        $categories = ['Alimentation', 'Transport', 'Loisirs', 'Santé'];
        foreach ($categories as $categoryName) {
            $category = new Category();
            $category->setName($categoryName);
            $this->entityManager->persist($category);
        }
        $this->entityManager->flush();

        // QUAND il consulte les catégories disponibles
        $this->client->request('GET', '/api/categories');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        
        $availableCategories = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(4, $availableCategories);

        // ET qu'il ajoute une nouvelle dépense
        $this->client->request('POST', '/api/expense', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'label' => 'Courses au supermarché',
            'amount' => 45.80,
            'date' => '2024-01-15',
            'category' => 'Alimentation'
        ]));

        // ALORS sa dépense est enregistrée avec succès
        $this->assertEquals(201, $this->client->getResponse()->getStatusCode());

        // ET elle apparaît dans sa liste de dépenses
        $this->client->request('GET', '/api/expenses');
        $expenses = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertCount(1, $expenses);
        $this->assertEquals('Courses au supermarché', $expenses[0]['label']);
        $this->assertEquals(45.80, $expenses[0]['amount']);
        $this->assertEquals('Alimentation', $expenses[0]['category']['name']);
    }

    /**
     * Scénario d'acceptation : Un utilisateur peut modifier ses dépenses
     */
    public function testUserCanModifyTheirExpenses(): void
    {
        // ÉTANT DONNÉ qu'un utilisateur a des dépenses enregistrées
        $user = $this->createTestUser('client-modification@bank.com');
        $category = $this->createTestCategory('Transport');
        $expense = $this->createTestExpense($user, $category, 'Ticket de métro', 2.50);

        $this->client->loginUser($user);

        // QUAND il modifie une de ses dépenses
        $this->client->request('PUT', "/api/expense/{$expense->getId()}", [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'label' => 'Ticket de bus',
            'amount' => 3.00
        ]));

        // ALORS la modification est sauvegardée
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        // ET les nouvelles données apparaissent
        $this->client->request('GET', "/api/expense/{$expense->getId()}");
        $updatedExpense = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertEquals('Ticket de bus', $updatedExpense['label']);
        $this->assertEquals(3.00, $updatedExpense['amount']);
    }

    /**
     * Scénario d'acceptation : Un utilisateur peut supprimer ses dépenses
     */
    public function testUserCanDeleteTheirExpenses(): void
    {
        // ÉTANT DONNÉ qu'un utilisateur a des dépenses enregistrées
        $user = $this->createTestUser('client-suppression@bank.com');
        $category = $this->createTestCategory('Loisirs');
        $expense = $this->createTestExpense($user, $category, 'Cinéma', 12.00);

        $this->client->loginUser($user);

        // QUAND il supprime une de ses dépenses
        $this->client->request('DELETE', "/api/expense/{$expense->getId()}");

        // ALORS la dépense est supprimée
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        // ET elle n'apparaît plus dans sa liste
        $this->client->request('GET', '/api/expenses');
        $expenses = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertEmpty($expenses);
    }

    /**
     * Scénario d'acceptation : La sécurité des données utilisateur est assurée
     */
    public function testUserDataSecurityIsEnsured(): void
    {
        // ÉTANT DONNÉ que deux utilisateurs ont des dépenses
        $user1 = $this->createTestUser('client1@bank.com');
        $user2 = $this->createTestUser('client2@bank.com');
        $category = $this->createTestCategory('Privé');
        
        $expenseUser1 = $this->createTestExpense($user1, $category, 'Dépense privée User1', 100.00);
        $expenseUser2 = $this->createTestExpense($user2, $category, 'Dépense privée User2', 200.00);

        // QUAND l'utilisateur 1 se connecte
        $this->client->loginUser($user1);

        // ALORS il ne voit que ses propres dépenses
        $this->client->request('GET', '/api/expenses');
        $expenses = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertCount(1, $expenses);
        $this->assertEquals('Dépense privée User1', $expenses[0]['label']);

        // ET il ne peut pas accéder aux dépenses de l'autre utilisateur
        $this->client->request('GET', "/api/expense/{$expenseUser2->getId()}");
        $this->assertEquals(403, $this->client->getResponse()->getStatusCode());

        // ET il ne peut pas modifier les dépenses de l'autre utilisateur
        $this->client->request('PUT', "/api/expense/{$expenseUser2->getId()}", [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['label' => 'Tentative de hack']));
        $this->assertEquals(403, $this->client->getResponse()->getStatusCode());
    }

    /**
     * Scénario d'acceptation : L'application gère les erreurs utilisateur gracieusement
     */
    public function testApplicationHandlesUserErrorsGracefully(): void
    {
        $user = $this->createTestUser('client-erreurs@bank.com');
        $this->client->loginUser($user);

        // QUAND l'utilisateur fait des erreurs de saisie
        
        // Erreur : montant négatif
        $this->client->request('POST', '/api/expense', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'label' => 'Test',
            'amount' => -10,
            'date' => '2024-01-15',
            'category' => 'Test'
        ]));

        // ALORS l'application retourne une erreur claire
        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());

        // Erreur : date invalide
        $this->client->request('POST', '/api/expense', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'label' => 'Test',
            'amount' => 10,
            'date' => 'date-invalide',
            'category' => 'Test'
        ]));

        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());

        // Erreur : catégorie inexistante
        $this->client->request('POST', '/api/expense', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'label' => 'Test',
            'amount' => 10,
            'date' => '2024-01-15',
            'category' => 'CatégorieInexistante'
        ]));

        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());
    }

    // Méthodes utilitaires
    private function createTestUser(string $email): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        return $user;
    }

    private function createTestCategory(string $name): Category
    {
        $category = new Category();
        $category->setName($name);
        $this->entityManager->persist($category);
        $this->entityManager->flush();
        return $category;
    }

    private function createTestExpense(User $user, Category $category, string $label, float $amount): Expense
    {
        $expense = new Expense();
        $expense->setLabel($label);
        $expense->setAmount($amount);
        $expense->setDate(new \DateTimeImmutable());
        $expense->setCategory($category);
        $expense->setUser($user);
        $this->entityManager->persist($expense);
        $this->entityManager->flush();
        return $expense;
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
