<?php

namespace LiamW\XenForoLicenseVerification\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class XenForoLicenseData extends Entity
{
	public static function getStructure(Structure $structure)
	{
		$structure->shortName = 'LiamW\XenForoLicenseVerification:XenForoLicenseData';
		$structure->table = 'xf_liamw_xenforo_license_data';
		$structure->primaryKey = 'user_id';

		$structure->columns = [
			'user_id' => [
				'type' => self::UINT,
				'required' => true
			],
			'customer_token' => [
				'type' => self::STR,
				'maxLength' => 50,
				'required' => true
			],
			'license_token' => [
				'type' => self::STR,
				'maxLength' => 50,
				'required' => true
			],
			'domain' => [
				'type' => self::STR,
				'maxLength' => 255,
				'required' => true
			],
			'domain_match' => [
				'type' => self::BOOL,
				'required' => true
			],
			'can_transfer' => [
				'type' => self::BOOL,
				'required' => true
			],
			'check_date' => [
				'type' => self::UINT,
				'default' => \XF::$time
			]
		];

		return $structure;
	}
}