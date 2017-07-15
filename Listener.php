<?php

namespace LiamW\XenForoLicenseVerification;

use XF\Mvc\Entity\Entity;

class Listener
{
	public static function userEntityStructure(\XF\Mvc\Entity\Manager $em, \XF\Mvc\Entity\Structure &$structure)
	{
		$structure->columns['xf_customer_token'] = [
			'type' => Entity::STR,
			'default' => null,
			'nullable' => true
		];
		$structure->columns['xf_validation_date'] = [
			'type' => Entity::UINT,
			'default' => 0
		];
	}
}