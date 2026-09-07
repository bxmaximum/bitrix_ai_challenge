<?php

declare(strict_types=1);

namespace Bxmax\Booking\Console;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Bxmax\Booking\Service\ScheduleGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * php bitrix/bitrix.php bxmax:booking:schedule [--days=14]
 *
 * Догенерирует слоты расписания. Повторный запуск безопасен: дубли отсекает уникальный индекс.
 */
final class GenerateScheduleCommand extends Command
{
	protected function configure(): void
	{
		$this
			->setName('bxmax:booking:schedule')
			->setDescription('Генерирует слоты расписания для всех мастеров')
			->addOption('days', null, InputOption::VALUE_REQUIRED, 'Горизонт в днях', (string)ScheduleGenerator::DAYS);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		if (!Loader::includeModule('bxmax.booking'))
		{
			$output->writeln('<error>Модуль bxmax.booking не установлен.</error>');

			return self::FAILURE;
		}

		$days = max(1, (int)$input->getOption('days'));

		/** @var ScheduleGenerator $generator */
		$generator = ServiceLocator::getInstance()->get(ScheduleGenerator::class);
		$stat = $generator->generate($days);

		$output->writeln(sprintf(
			'Мастеров: %d, запланировано слотов: %d, создано новых: %d.',
			$stat['masters'],
			$stat['planned'],
			$stat['created']
		));

		return self::SUCCESS;
	}
}
