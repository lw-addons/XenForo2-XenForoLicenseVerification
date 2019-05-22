<?php

namespace LiamW\XenForoLicenseVerification\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * COLUMNS
 *
 * @property int user_id
 * @property string validation_token
 * @property string customer_token
 * @property string license_token
 * @property string|null domain
 * @property bool|null domain_match
 * @property bool can_transfer
 * @property int validation_date
 */
class XenForoLicenseData extends Entity
{
	protected function setupApiResultData(\XF\Api\Result\EntityResult $result, $verbosity = self::VERBOSITY_NORMAL, array $options = [])
	{
	}

	public function deleteLicenseData($removeCustomerToken = false)
	{
		if (!\XF::options()->liamw_xenforolicenseverification_maintain_customer || $removeCustomerToken)
		{
			$this->delete();
		}
		else
		{
			$this->validation_token = null;
			$this->license_token = null;
			$this->domain = null;
			$this->domain_match = null;
			$this->can_transfer = null;
			$this->validation_date = null;
			$this->save();
		}
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
				'nullable' => true,
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
				'nullable' => true,
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
				'nullable' => true,
				'api' => true
			],
			'validation_date' => [
				'type' => self::UINT,
				'default' => \XF::$time,
				'nullable' => true,
				'api' => true
			]
		];

		return $structure;
	}
}