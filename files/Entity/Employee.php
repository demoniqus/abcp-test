<?php

namespace NW\WebService\Entity;


use NW\WebService\Interfaces\UserModelInterface;

final class Employee extends AbstractUser
{
	public const TYPE = UserModelInterface::USER_EMPLOYEE_TYPE;

}