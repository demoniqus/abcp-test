<?php

namespace NW\WebService\NotificationServices;

use NW\WebService\Entity\Contractor;
use NW\WebService\Entity\Seller;


final class NotificationManager {

	public static function send(
		Seller $seller,
		Contractor $client,
		string $notificationEvent,
		int $differenceTo,
		array $template,
		&$error
	): bool
	{
		//fake method
		return true;
	}

}