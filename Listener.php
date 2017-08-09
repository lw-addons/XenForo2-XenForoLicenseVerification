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
			'primary' => true,
			'cascadeDelete' => true
		];
	}

	public static function userChange(\XF\Service\User\ContentChange $changeService, array &$updates)
	{
		$updates['xf_liamw_xenforo_license_data'] = ['user_id', 'emptyable' => false];
	}
}