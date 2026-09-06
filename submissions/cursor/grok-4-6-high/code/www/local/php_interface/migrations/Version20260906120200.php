<?php

namespace Sprint\Migration;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Bxmax\Booking\Application\Service\ScheduleGenerator;
use CSite;

class Version20260906120200 extends Version
{
    protected $author = 'bxmax';
    protected $description = 'Generate slots, assign site template';
    protected $moduleVersion = '5.13.0';

    public function up()
    {
        $this->checkRequiredVersions(['Version20260906120000', 'Version20260906120100']);

        if (!Loader::includeModule('bxmax.booking')) {
            $this->outError('bxmax.booking is not installed');
            return false;
        }

        $generator = ServiceLocator::getInstance()->get(ScheduleGenerator::class);
        $count = $generator->generateForExistingMasters();
        $this->outSuccess('Slots created: ' . $count);

        $site = new CSite();
        $site->Update('s1', [
            'NAME' => 'Лак&Точка',
            'SITE_NAME' => 'Лак&Точка',
            'TEMPLATE' => [
                [
                    'CONDITION' => '',
                    'SORT' => 1,
                    'TEMPLATE' => 'laktochka',
                ],
            ],
        ]);

        $helper = $this->getHelperManager();
        $helper->Site()->setSiteTemplates('s1', [
            ['TEMPLATE' => 'laktochka', 'CONDITION' => ''],
        ]);

        $this->outSuccess('Site template laktochka assigned');
    }

    public function down()
    {
        // slots drop together with the module tables
    }
}
