<?php declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Netzkollektiv\EasyCredit\Setting\Service\ApiCredentialServiceInterface;
use Netzkollektiv\EasyCredit\Api\IntegrationFactory;
use Netzkollektiv\EasyCredit\Setting\Service\SettingsServiceInterface;

class SettingsController extends AbstractController
{
    private ApiCredentialServiceInterface $apiCredentialTestService;

    private SettingsServiceInterface $settings;

    private IntegrationFactory $integrationFactory;

    public function __construct(
        ApiCredentialServiceInterface $apiService,
        SettingsServiceInterface $settingsService,
        IntegrationFactory $integrationFactory
    ) {
        $this->apiCredentialTestService = $apiService;
        $this->settings = $settingsService;
        $this->integrationFactory = $integrationFactory;
    }

    public function validateApiCredentials(Request $request): JsonResponse
    {
        $webshopId = $request->query->get('webshopId') ?? '';
        $apiPassword = $request->query->get('apiPassword') ?? '';
        $apiSignature = $request->query->get('apiSignature') ?? '';

        $credentialsValid = $this->apiCredentialTestService->testApiCredentials($webshopId, $apiPassword, $apiSignature);

        if ($credentialsValid) {
            $this->settings->updateSettings([
                'webshopId' => $webshopId,
                'apiPassword' => $apiPassword,
                'apiSignature' => $apiSignature
            ]);

            $webshopInfo = $this->integrationFactory
                ->createCheckout(null, false)
                ->getWebshopDetails();

            $this->settings->updateSettings(['webshopInfo' => $webshopInfo]);
        }

        return new JsonResponse(['credentialsValid' => $credentialsValid]);
    }

    public function validateApiCredentialsLegacy(Request $request): JsonResponse
    {
        return $this->validateApiCredentials($request);
    }
}
