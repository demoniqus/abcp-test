<?php
namespace NW\WebService\References\Operations\Notification;


use NW\WebService\Entity\Seller;


function getResellerEmailFrom(Seller $reseller): string
{
	return 'contractor@example.com';
}

function getEmailsByPermit(Seller $reseller, $event): array
{
	// fakes the method
	return ['someemeil@example.com', 'someemeil2@example.com'];
}

function __ () {
	//fake function - заглушка вместо Wordpress
	return var_export(func_get_args(), true);
}