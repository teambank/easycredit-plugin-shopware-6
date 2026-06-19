<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Netzkollektiv\EasyCredit\Setting\Service\ApiCredentialServiceInterface;

class SettingsController extends AbstractController
{
    private ApiCredentialServiceInterface $apiCredentialTestService;

    public function __construct(
        ApiCredentialServiceInterface $apiService
    ) {
        $this->apiCredentialTestService = $apiService;
    }

    public function validateApiCredentials(Request $request): JsonResponse
    {
        $payload = $request->toArray();
        $webshopId = (string) ($payload['webshopId'] ?? '');
        $apiPassword = (string) ($payload['apiPassword'] ?? '');
        $apiSignature = (string) ($payload['apiSignature'] ?? '');

        $credentialsValid = $this->apiCredentialTestService->testApiCredentials($webshopId, $apiPassword, $apiSignature);

        return new JsonResponse(['credentialsValid' => $credentialsValid]);
    }

    public function validateApiCredentialsLegacy(Request $request): JsonResponse
    {
        return $this->validateApiCredentials($request);
    }
}
