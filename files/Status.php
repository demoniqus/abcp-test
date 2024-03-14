<?php

namespace NW\WebService\References\Operations\Notification;


final class Status
{
	public const STATES = [
		0 => 'Completed',
		1 => 'Pending',
		2 => 'Rejected',
	];

	public static function getName(int $id): string
	{

		if (!array_key_exists($id, static::STATES)) {
			throw new \Exception('Undefined status "' . $id . '"');
		}

		return static::STATES[$id];
	}
}