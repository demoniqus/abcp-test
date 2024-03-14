<?php

namespace NW\WebService\Interfaces;


interface UserModelInterface
{
	const USER_CONTRACTOR_TYPE = 0;
	const USER_EMPLOYEE_TYPE = UserModelInterface::USER_CONTRACTOR_TYPE + 1;
	const USER_SELLER_TYPE = UserModelInterface::USER_EMPLOYEE_TYPE + 1;

}