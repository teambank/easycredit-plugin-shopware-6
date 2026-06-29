<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Setting\Service;

use Netzkollektiv\EasyCredit\Api\IntegrationFactory;
use Netzkollektiv\EasyCredit\Setting\Exception\ApiCredentialsInvalidException;
use Netzkollektiv\EasyCredit\Setting\Exception\ApiCredentialsNotActiveException;
use Teambank\EasyCreditApiV3\ApiException;
use Teambank\EasyCreditApiV3\Integration\ApiCredentialsInvalidException as IntegrationApiCredentialsInvalidException;
use Teambank\EasyCreditApiV3\Integration\ApiCredentialsNotActiveException as IntegrationApiCredentialsNotActiveException;

class ApiCredentialService implements ApiCredentialServiceInterface
{
    private IntegrationFactory $integrationFactory;

    public function __construct(
        IntegrationFactory $integrationFactory
    ) {
        $this->integrationFactory = $integrationFactory;
    }

    /**
     * @throws ApiCredentialsInvalidException
     * @throws ApiCredentialsNotActiveException
     */
    public function testApiCredentials(string $webshopId, string $apiPassword, string $apiSignature = null): bool
    {
        if (!$webshopId || !$apiPassword) {
            throw new ApiCredentialsInvalidException();
        }

        try {
            $checkout = $this->integrationFactory->createCheckout(null, false);
            $checkout->verifyCredentials($webshopId, $apiPassword, $apiSignature);
        } catch (IntegrationApiCredentialsInvalidException $e) {
            throw new ApiCredentialsInvalidException();
        } catch (IntegrationApiCredentialsNotActiveException $e) {
            throw new ApiCredentialsNotActiveException();
        } catch (ApiException $e) {
            throw new ApiCredentialsInvalidException();
        }

        return true;
    }
}
