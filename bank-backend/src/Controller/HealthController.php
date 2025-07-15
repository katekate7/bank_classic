<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class HealthController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/health', name: 'api_health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        $status = 'ok';
        $checks = [];

        try {
            // Check database connectivity
            $this->entityManager->getConnection()->executeQuery('SELECT 1');
            $checks['database'] = 'ok';
        } catch (\Exception $e) {
            $status = 'error';
            $checks['database'] = 'error: ' . $e->getMessage();
        }

        // Check PHP version
        $checks['php_version'] = PHP_VERSION;

        // Check if in production environment
        $checks['environment'] = $this->getParameter('kernel.environment');

        // Check memory usage
        $checks['memory_usage'] = [
            'used' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
            'limit' => ini_get('memory_limit')
        ];

        // Check disk space (if possible)
        try {
            $checks['disk_space'] = [
                'free' => disk_free_space('./'),
                'total' => disk_total_space('./')
            ];
        } catch (\Exception $e) {
            $checks['disk_space'] = 'unavailable';
        }

        return new JsonResponse([
            'status' => $status,
            'timestamp' => (new \DateTime())->format('c'),
            'version' => '1.0.0',
            'checks' => $checks
        ]);
    }

    #[Route('/status', name: 'api_status', methods: ['GET'])]
    public function status(): JsonResponse
    {
        return new JsonResponse([
            'application' => 'Banking Application',
            'version' => '1.0.0',
            'environment' => $this->getParameter('kernel.environment'),
            'timestamp' => (new \DateTime())->format('c'),
            'uptime' => $this->getUptime()
        ]);
    }

    private function getUptime(): array
    {
        $uptimeSeconds = null;
        
        // Try to get system uptime on Unix systems
        if (function_exists('sys_getloadavg') && file_exists('/proc/uptime')) {
            $uptime = file_get_contents('/proc/uptime');
            $uptimeSeconds = (float) strtok($uptime, ' ');
        }

        if ($uptimeSeconds !== null) {
            $days = floor($uptimeSeconds / 86400);
            $hours = floor(($uptimeSeconds % 86400) / 3600);
            $minutes = floor(($uptimeSeconds % 3600) / 60);
            $seconds = $uptimeSeconds % 60;

            return [
                'seconds' => $uptimeSeconds,
                'formatted' => sprintf('%dd %dh %dm %ds', $days, $hours, $minutes, $seconds)
            ];
        }

        return [
            'seconds' => null,
            'formatted' => 'unavailable'
        ];
    }
}
