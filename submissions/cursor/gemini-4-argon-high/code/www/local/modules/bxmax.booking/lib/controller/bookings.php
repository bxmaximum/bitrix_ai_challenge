<?php declare(strict_types=1);

namespace Bxmax\Booking\Controller;

use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\JsonPayload;
use Bxmax\Booking\Service\BookingService;

final class Bookings extends Controller
{
    public function configureActions(): array
    {
        return [
            'create' => [
                '-prefilters' => [
                    ActionFilter\Authentication::class,
                    ActionFilter\Csrf::class,
                ],
                '+prefilters' => [
                    new ActionFilter\HttpMethod([
                        ActionFilter\HttpMethod::METHOD_POST,
                    ]),
                    new ActionFilter\CloseSession(),
                ],
            ],
        ];
    }

    public function createAction(BookingService $bookingService): ?array
    {
        $params = $this->extractRequestParams();

        $input = [
            'slotId' => $params['slotId'] ?? null,
            'serviceId' => $params['serviceId'] ?? null,
            'name' => $params['name'] ?? null,
            'phone' => $params['phone'] ?? null,
            'consent' => $params['consent'] ?? null,
        ];

        $result = $bookingService->createBooking($input);
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
