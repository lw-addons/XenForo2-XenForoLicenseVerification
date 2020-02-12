<?php

namespace LiamW\XenForoLicenseVerification\XF\ChangeLog;

class User extends XFCP_User
{
	protected function getLabelMap()
	{
		$labelMap = parent::getLabelMap();

		$labelMap = array_merge($labelMap, [
			'validation_token' => 'liamw_xenforolicenseverification_xenforo_license_validation_token',
			'customer_token' => 'liamw_xenforolicenseverification_xenforo_customer_token',
			'license_token' => 'liamw_xenforolicenseverification_xenforo_license_license_token',
			'domain' => 'liamw_xenforolicenseverification_xenforo_license_validation_domain',
			'can_transfer' => 'liamw_xenforolicenseverification_xenforo_license_transferable',
			'validation_date' => 'liamw_xenforolicenseverification_xenforo_license_validation_date'
		]);

		return $labelMap;
	}

	protected function getFormatterMap()
	{
		$formatterMap = parent::getFormatterMap();

		$formatterMap = array_merge($formatterMap, [
			'domain_match' => 'formatYesNo',
			'can_transfer' => 'formatYesNo',
			'validation_date' => 'formatDateTime'
		]);

		return $formatterMap;
	}
}