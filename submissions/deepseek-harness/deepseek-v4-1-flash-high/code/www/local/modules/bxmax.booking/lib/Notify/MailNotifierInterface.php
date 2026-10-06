<?php

namespace Bxmax\Booking\Notify;

/**
 * Boundary between the booking domain and the notification channel.
 */
interface MailNotifierInterface
{
	/**
	 * @param array{id:int, name:string, phone:string, consentAt:string} $booking
	 * @param array{startsAt:string, endsAt:string} $slot
	 * @param array{name:string, price:int} $service
	 * @param array{name:string} $master
	 */
	public function notifyAdminAboutBooking(array $booking, array $slot, array $service, array $master): void;
}
