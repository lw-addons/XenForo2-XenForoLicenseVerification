<?php

namespace LiamW\XenForoLicenseVerification\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class XenForoLicenseData extends Entity
{
	protected function setupApiResultData(\XF\Api\Result\EntityResult $result, $verbosity = self::VERBOSITY_NORMAL, array $options = [])
	{
	}

	public static function getStructure(Structure $structure)
	{
		$structure->shortName = 'LiamW\XenForoLicenseVerification:XenForoLicenseData';
		$structure->table = 'xf_liamw_xenforo_license_data';
		$structure->primaryKey = 'user_id';

		$structure->columns = [
			'user_id' => [
				'type' => self::UINT,
				'required' => true,
				'api' => true
			],
			'validation_token' => [
				'type' => self::STR,
				'maxLength' => 50,
				'required' => true,
				'api' => true
			],
			'customer_token' => [
				'type' => self::STR,
				'maxLength' => 50,
				'required' => true,
				'api' => true
			],
			'license_token' => [
				'type' => self::STR,
				'maxLength' => 50,
				'required' => true,
				'api' => true
			],
			'domain' => [
				'type' => self::STR,
				'maxLength' => 255,
				'required' => true,
				'nullable' => true,
				'api' => true
			],
			'domain_match' => [
				'type' => self::BOOL,
				'required' => true,
				'nullable' => true,
				'api' => true
			],
			'can_transfer' => [
				'type' => self::BOOL,
				'required' => true,
				'api' => true
			],
			'validation_date' => [
				'type' => self::UINT,
				'default' => \XF::$time,
				'api' => true
			]
		];

		return $structure;
	}
}