<?php

namespace LiamW\XenForoLicenseVerification;

use XF\Mvc\Entity\Entity;

class Listener
{
	public static function userEntityStructure(\XF\Mvc\Entity\Manager $em, \XF\Mvc\Entity\Structure &$structure)
	{
		$structure->relations['XenForoLicense'] = [
			'entity' => 'LiamW\XenForoLicenseVerification:XenForoLicenseData',
			'type' => Entity::TO_ONE,
			'conditions' => 'user_id',
			'primary' => true
		];
	}
}