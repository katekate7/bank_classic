<?php

namespace App\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Base class for integration tests that need a real database
 * This ensures proper database schema creation for true integration testing
 */
abstract class DatabaseTestCase extends KernelTestCase
{
    protected EntityManagerInterface $entityManager;
    private static bool $databaseInitialized = false;

    protected function setUp(): void
    {
        parent::setUp();
        
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        // Only create schema once per test run for performance
        if (!self::$databaseInitialized) {
            $this->initializeDatabase();
            self::$databaseInitialized = true;
        }

        // Start transaction for test isolation
        $this->entityManager->beginTransaction();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Rollback transaction to clean up test data
        if ($this->entityManager->getConnection()->isTransactionActive()) {
            $this->entityManager->rollback();
        }
        
        $this->entityManager->close();
    }

    private function initializeDatabase(): void
    {
        // Drop and recreate database schema
        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        
        // Drop existing schema
        $schemaTool->dropSchema($metadata);
        
        // Create fresh schema
        $schemaTool->createSchema($metadata);
    }

    protected function persistAndFlush(object $entity): void
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }
}
