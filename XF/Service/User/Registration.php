<?php

namespace LiamW\XenForoLicenseVerification\XF\Service\User;

class Registration extends XFCP_Registration
{
	protected function validateXenForoLicense(array $validationData)
	{
		parent::applyExtraValidation();

		// Prevent definitely wrong token from being checked and using up requests
		if (!$validationData['token'] || strlen($validationData['token']) != 32 || !preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $validationData['token']))
		{
			$this->user->error(\XF::phrase('liamw_xenforolicenseverification_invalid_verification_token'));

			return;
		}

		if (!$validationData['domain'])
		{
			$this->user->error(\XF::phrase('liamw_xenforolicenseverification_invalid_domain'));

			return;
		}

		/** @var \LiamW\XenForoLicenseVerification\Service\LicenseValidator $validationService */
		$validationService = $this->service('LiamW\XenForoLicenseVerification:LicenseValidator', $validationData['token'], $validationData['domain'], [
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

		if ($input['license_validation'])
		{
			$this->validateXenForoLicense($input['license_validation']);
		}
	}
}