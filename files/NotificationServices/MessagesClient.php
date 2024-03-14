<?php

namespace NW\WebService\NotificationServices;

use NW\WebService\Entity\Contractor;
use NW\WebService\Entity\Seller;
final class MessagesClient
{
	public static function sendMessage(
		array $message,
		Seller $seller,
		?Contractor $client,
		string $notificationEvent,
		?int $differenceTo
	):bool
	{
		//fake method
		return true;
	}

}