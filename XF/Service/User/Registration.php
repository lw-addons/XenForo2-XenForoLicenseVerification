<?php

namespace LiamW\XenForoLicenseVerification\XF\Service\User;

class Registration extends XFCP_Registration
{
	protected $licenseValidation = [];

	protected function applyExtraValidation()
	{
		parent::applyExtraValidation();

		// Prevent definitely wrong token from being checked and using up requests
		if (!$this->licenseValidation['token'] || strlen($this->licenseValidation['token']) != 32 || !preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $this->licenseValidation['token']))
		{
			$this->user->error(\XF::phrase('liamw_xenforolicenseverification_invalid_verification_token'));

			return;
		}

		if (!$this->licenseValidation['domain'])
		{
			$this->user->error(\XF::phrase('liamw_xenforolicenseverification_invalid_domain'));

			return;
		}

		/** @var \LiamW\XenForoLicenseVerification\Service\LicenseValidator $validationService */
		$validationService = $this->service('LiamW\XenForoLicenseVerification:LicenseValidator', $this->licenseValidation['token'], $this->licenseValidation['domain'], [
			'requireUniqueCustomer' => $this->app->options()->liamw_xenforolicensevalidation_unique_customer
		]);

		if (!$validationService->validate()->isValid(true, $error))
		{
			$this->user->error($error);
		}
		else
		{
			$validationService->setDetailsOnUser($this->user);
		}
	}

	public function setFromInput(array $input)
	{
		parent::setFromInput($input);

		$this->licenseValidation = $input['license_validation'];
	}
}