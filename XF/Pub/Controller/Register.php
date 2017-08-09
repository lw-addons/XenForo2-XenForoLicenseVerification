<?php

namespace LiamW\XenForoLicenseVerification\XF\Pub\Controller;

use XF\ConnectedAccount\ProviderData\AbstractProviderData;

class Register extends XFCP_Register
{
	protected function setupRegistration(array $input)
	{
		$input += $this->filter([
			'xenforo_license_verification' => [
				'token' => 'str',
				'domain' => 'str'
			]
		]);

		return parent::setupRegistration($input);
	}

	protected function setupConnectedRegistration(array $input, AbstractProviderData $providerData)
	{
		$input += $this->filter([
			'xenforo_license_verification' => [
				'token' => 'str',
				'domain' => 'str'
			]
		]);

		return parent::setupConnectedRegistration($input, $providerData);
	}
}