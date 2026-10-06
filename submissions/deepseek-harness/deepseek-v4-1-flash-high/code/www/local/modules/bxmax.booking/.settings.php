<?php

use Bxmax\Booking\Notify\AdminNotifier;
use Bxmax\Booking\Notify\MailNotifierInterface;

return [
	'controllers' => [
		'value' => [
			// bxmax:booking.api.slots.list -> \Bxmax\Booking\Controller\Api\Slots::listAction()
			'defaultNamespace' => '\Bxmax\Booking\Controller',
		],
		'readonly' => true,
	],
	'services' => [
		'value' => [
			// Concrete repositories and services are autowired by their class
			// names; only the notification boundary needs an explicit binding.
			MailNotifierInterface::class => [
				'className' => AdminNotifier::class,
			],
		],
		'readonly' => true,
	],
];
