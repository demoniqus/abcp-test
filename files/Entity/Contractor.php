<?php

namespace NW\WebService\Entity;

use NW\WebService\Interfaces\UserModelInterface;

final class Contractor extends AbstractUser
{
	public const TYPE = UserModelInterface::USER_CONTRACTOR_TYPE;
	private ?string $email = null;
	private ?string $mobile = null;
	private ?Seller $seller = null;

	public function getSeller(): ?Seller
	{
		return $this->seller;
	}

	public function getEmail(): ?string
	{
		return $this->email;
	}

	public function getMobile(): ?string
	{
		return $this->mobile;
	}


	public static function getById(int $id): Contractor
	{
		//fake method
		$contractor = parent::getById($id);

		$contractor->seller = Seller::getById(7777);

		return $contractor;
	}
}