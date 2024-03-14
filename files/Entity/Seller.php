<?php

namespace NW\WebService\Entity;

use NW\WebService\Interfaces\UserModelInterface;


final class Seller extends AbstractUser
{
	public const TYPE = UserModelInterface::USER_SELLER_TYPE;
}