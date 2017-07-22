<?php

namespace LiamW\XenForoLicenseVerification\XF\Pub\Controller;

class Register extends XFCP_Register
{
	protected function getRegistrationInput(\XF\Service\User\RegisterForm $regForm)
	{
		$input = parent::getRegistrationInput($regForm);

		$input += $this->filter([
			'license_validation' => [
				'token' => 'str',
				'domain' => 'str'
			]
		]);

		return $input;
	}
}