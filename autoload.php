<?php

spl_autoload_register(
/**
 * @throws \Exception
 */
	function($classQualifier){

		$path = '.' . DIRECTORY_SEPARATOR . 'files';

		include_once ($path . DIRECTORY_SEPARATOR . 'funcs.php');

		$components = explode('\\', $classQualifier);
		$className = '';
		$prefix = '';
		while (count($components)) {
			$className = array_pop($components);
			if ($className) {
				$prefix = array_pop($components);
				break;
			}
		}

		$allowedPrefixes = [
			'Entity' => true,
			'Interfaces' => true,
			'NotificationServices' => true,
		];

		if ($prefix && array_key_exists($prefix, $allowedPrefixes)) {
			$prefix .= DIRECTORY_SEPARATOR;
		}
		else {
			$prefix = '';
		}
		$objectPath = $path . DIRECTORY_SEPARATOR . $prefix . $className . '.php';

		if (is_file($objectPath)) {
			include_once $objectPath;
		} else {
			throw new \Exception('Не удалось загрузить файлы проекта (' . $objectPath . '). Обратитесь к разработчику.');
		}
	}
);