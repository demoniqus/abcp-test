<?php

namespace NW\WebService\References\Operations\Notification;

use NW\WebService\Interfaces\RequestModelInterface;

abstract class AbstractReferencesOperation
{

	public const ALLOWED_NOTIFICATION_TYPES = [];

	public const NOTIFICATION_TYPE = 'notificationType';
	abstract public function doOperation(): array;

	protected function getNotificationType(): int
	{
		$notificationType = $this->get(RequestModelInterface::NOTIFICATION_TYPE);

		if (null === $notificationType) {
			throw new \Exception('Empty notification type');
		}

		$notificationType = (int)$notificationType;

		if (!array_key_exists($notificationType, static::ALLOWED_NOTIFICATION_TYPES)) {
			throw new \Exception('Denied notification type');
		}

		return $notificationType;
	}

	protected function get(string $key)
	{
		return ($_REQUEST[RequestModelInterface::DATA] ?? [])[$key] ?? null;
	}

	protected function getRequest($pName)
	{
		return $_REQUEST[$pName] ?? null;
	}


}