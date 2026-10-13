<?php declare(strict_types=1);

namespace Bxmax\Booking\Controller;

use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\JsonPayload;
use Bxmax\Booking\Service\SlotService;

final class Slots extends Controller
{
    public function configureActions(): array
    {
        return [
            'list' => [
                '-prefilters' => [
                    ActionFilter\Authentication::class,
                    ActionFilter\Csrf::class,
                ],
                '+prefilters' => [
                    new ActionFilter\HttpMethod([
                        ActionFilter\HttpMethod::METHOD_GET,
                        ActionFilter\HttpMethod::METHOD_POST,
                    ]),
                    new ActionFilter\CloseSession(),
                ],
            ],
        ];
    }

    public function listAction(SlotService $slotService): ?array
    {
        $params = $this->extractRequestParams();

        $rawMasterId = $params['masterId'] ?? null;
        $rawWeekStart = isset($params['weekStart']) && is_scalar($params['weekStart'])
            ? (string)$params['weekStart']
            : null;

        $parsedMasterId = is_numeric($rawMasterId) ? (int)$rawMasterId : 0;
        $result = $slotService->getMasterWeekSlots($parsedMasterId, $rawWeekStart);

        if (!$result->isSuccess())
        {
            $this->addErrors($result->getErrors());
            return null;
        }

        return $result->getData();
    }

    /**
     * @return array<string, mixed>
     */
    private function extractRequestParams(): array
    {
        $request = $this->getRequest();
        $params = array_merge(
            $request->getQueryList()->toArray(),
            $request->getPostList()->toArray()
        );

        try
        {
            $jsonPayload = new JsonPayload();
            $jsonData = $jsonPayload->getData();
            if (is_array($jsonData))
            {
                $params = array_merge($params, $jsonData);
            }
        }
        catch (\Throwable)
        {
        }

        return $params;
    }
}
