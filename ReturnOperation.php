<?php

namespace NW\WebService\References\Operations\Notification;

use NW\WebService\Entity\AbstractUser;
use NW\WebService\Entity\Contractor;
use NW\WebService\Entity\Employee;
use NW\WebService\Entity\Seller;
use NW\WebService\Interfaces\NotificationEventsInterface;
use NW\WebService\Interfaces\RequestModelInterface;
use NW\WebService\Interfaces\UserModelInterface;
use NW\WebService\NotificationServices\MessagesClient;
use NW\WebService\NotificationServices\NotificationManager;

require_once 'autoload.php';

class TsReturnOperation extends AbstractReferencesOperation
{
	public const TYPE_NEW    = 1;
	public const TYPE_CHANGE = 2;
	public const ALLOWED_NOTIFICATION_TYPES = [
		self::TYPE_NEW => true,
		self::TYPE_CHANGE => true,
	];

	private function getMember(
		string $memberIdKey,
		int $type,
		string $className,
		string  $emptyIdMessage,
		string $memberNotFoundMessage
	): AbstractUser
	{
		$memberId = $this->get($memberIdKey);

		if (!$memberId) {
			throw new \Exception($emptyIdMessage);
		}

		$member = $className::getById($memberId);

		if (
			!$member ||
			!$member->isType($type)
		) {
			throw new \Exception($memberNotFoundMessage);
		}

		return $member;
	}

	private function getSeller(): Seller
	{
		return $this->getMember(
			RequestModelInterface::RESELLER_ID,
			UserModelInterface::USER_SELLER_TYPE,
			Seller::class,
			'Empty resellerId.',
			'Seller not found.'
		);
	}

	private function getCreator(): Employee
	{
		return $this->getMember(
			RequestModelInterface::CREATOR_ID,
			UserModelInterface::USER_EMPLOYEE_TYPE,
			Employee::class,
			'Empty creatorId.',
			'Creator not found.'
		);
	}

	private function getExpert(): Employee
	{
		return $this->getMember(
			RequestModelInterface::EXPERT_ID,
			UserModelInterface::USER_EMPLOYEE_TYPE,
			Employee::class,
			'Empty expertId.',
			'Expert not found.'
		);
	}

	private function getClient(Seller $seller): Contractor
	{
		$client = $this->getMember(
			RequestModelInterface::CLIENT_ID,
			UserModelInterface::USER_CONTRACTOR_TYPE,
			Contractor::class,
			'Empty clientId.',
			'Client not found.'
		);

		if (
			!$client->getSeller() ||
			$client->getSeller()->getId() !== $seller->getId()
		) {
			throw new \Exception('Client not found');
		}

		return $client;
	}

	/**
	 * @param int $notificationType
	 * @param Seller $reseller
	 * @return string
	 * @throws \Exception
	 */
	private function getDifferences(int $notificationType, Seller $reseller): string
	{
		if ($notificationType === self::TYPE_NEW) {
			return  __('NewPositionAdded', null, $reseller->getId());
		}

		if (
			$notificationType === self::TYPE_CHANGE &&
			!empty($differences = $this->get(RequestModelInterface::DIFFERENCES)) &&
			is_array($differences)
		) {
			$from = (int)($differences[RequestModelInterface::FROM] ?? null);
			$to = (int)($differences[RequestModelInterface::TO] ?? null);
			return __('PositionStatusHasChanged', [
				'FROM' => Status::getName($from),
				'TO'   => Status::getName($to),
			], $reseller->getId());
		}
		throw new \Exception('Incorrect request data.');
	}
	/**
	 * @return int|null
	 * @throws \Exception
	 */
	private function getDifferenceTo(): ?int
	{
		if (
			!empty($differences = $this->get(RequestModelInterface::DIFFERENCES)) &&
			is_array($differences)
		) {
			return (int)($differences[RequestModelInterface::TO] ?? null);
		}
		return null;
	}

	/**
	 * @param array $templateData
	 * @return void
	 * @throws \Exception
	 */
	private function validateTemplate(array $templateData): void
	{
		// Если хоть одна переменная для шаблона не задана, то не отправляем уведомления
		foreach ($templateData as $key => $tempData) {
			if (
				(
					is_string($tempData) &&
					trim($tempData) === ''
				) ||
				empty($tempData)
			) {
				throw new \Exception("Template Data ({$key}) is empty!", 500);
			}
		}
	}

	private function notifyEmployees(
		Seller $reseller,
		array $templateData,
		?string $senderEmail
	): bool
	{
		// Получаем email сотрудников из настроек
		$employeeEmails = getEmailsByPermit($reseller, 'tsGoodsReturn');
		if (!empty($senderEmail) && count($employeeEmails)) {
			$messageTemplate = [
				'emailFrom' => $senderEmail,
				'subject'   => __('complaintEmployeeEmailSubject', $templateData, $reseller->getId()),
				'message'   => __('complaintEmployeeEmailBody', $templateData, $reseller->getId()),
			];
			foreach ($employeeEmails as $employeeEmail) {
				$messageTemplate['emailTo'] = $employeeEmail;
				MessagesClient::sendMessage(
					$messageTemplate,
					$reseller,
					null,
					NotificationEventsInterface::CHANGE_RETURN_STATUS,
					null
				);
			}
			return true;
		}
		return false;
	}

	/**
	 * @throws \Exception
	 */
	public function doOperation(): array
	{
		$notificationType = $this->getNotificationType();

		$reseller = $this->getSeller();
		$client = $this->getClient($reseller);
		$creator = $this->getCreator();
		$expert = $this->getExpert();

		$templateData = [
			'COMPLAINT_ID'       => (int)$this->get(RequestModelInterface::COMPLAINT_ID),
			'COMPLAINT_NUMBER'   => (string)$this->get(RequestModelInterface::COMPLAINT_NUMBER),
			'CREATOR_ID'         => $creator->getId(),
			'CREATOR_NAME'       => $creator->getFullName(),
			'EXPERT_ID'          => $expert->getId(),
			'EXPERT_NAME'        => $expert->getFullName(),
			'CLIENT_ID'          => $client->getId(),
			'CLIENT_NAME'        => $client->getFullName() ?: $client->getName(),
			'CONSUMPTION_ID'     => (int)$this->get(RequestModelInterface::CONSUMPTION_ID),
			'CONSUMPTION_NUMBER' => (string)$this->get(RequestModelInterface::CONSUMPTION_NUMBER),
			'AGREEMENT_NUMBER'   => (string)$this->get(RequestModelInterface::AGREEMENT_NUMBER),
			'DATE'               => (string)$this->get(RequestModelInterface::DATE),
			'DIFFERENCES'        => $this->getDifferences($notificationType, $reseller),
		];

		$this->validateTemplate($templateData);

		$senderEmail = getResellerEmailFrom($reseller);

		$acceptorEmails = getEmailsByPermit($reseller, 'tsGoodsReturn');

		$result = [
			'notificationEmployeeByEmail' => false,
			'notificationClientByEmail'   => false,
			'notificationClientBySms'     => [
				'isSent'  => false,
				'message' => '',
			],
		];

		if ($this->notifyEmployees($reseller, $templateData, $senderEmail)) {
			$result['notificationEmployeeByEmail'] = true;;
		}

		$differenceTo = $this->getDifferenceTo();

		if (
			$notificationType !== self::TYPE_CHANGE ||
			$differenceTo === null
			/** Для большей надежности можно еще проверить, что статусы from и to различаются, что нет ошибки программиста */
		) {
			return $result;
		}
		// Шлём клиентское уведомление, только если произошла смена статуса
		if (!(empty($senderEmail) && empty($client->getEmail()))) {
			$message = [
				'emailFrom' => $senderEmail,
				'emailTo'   => $client->getEmail(),
				'subject'   => __('complaintClientEmailSubject', $templateData, $reseller->getId()),
				'message'   => __('complaintClientEmailBody', $templateData, $reseller->getId()),
			];
			if (MessagesClient::sendMessage(
				$message,
				$reseller,
				$client,
				NotificationEventsInterface::CHANGE_RETURN_STATUS,
				$differenceTo
			)) {
				$result['notificationClientByEmail'] = true;
			}
		}

		if (!empty($client->getMobile())) {
			$res = NotificationManager::send(
				$reseller->getId(),
				$client->getId(),
				NotificationEventsInterface::CHANGE_RETURN_STATUS,
				$differenceTo,
				$templateData,
				$error
			);
			if ($res) {
				$result['notificationClientBySms']['isSent'] = true;
			}
			if (!empty($error)) {
				$result['notificationClientBySms']['message'] = $error;
			}
		}

		return $result;
	}
}