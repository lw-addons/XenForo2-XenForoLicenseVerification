<?php

namespace LiamW\XenForoLicenseVerification\XF\Service\User;

class Registration extends XFCP_Registration
{
	protected function validateXenForoLicense(array $validationData)
	{
		parent::applyExtraValidation();

		if ($this->app->options()->liamw_xenforolicensevalidation_registration['request'])
		{
			if ($this->app->options()->liamw_xenforolicensevalidation_registration['require'] && !$validationData['token'])
			{
				$this->user->error(\XF::phrase('liamw_xenforolicenseverification_invalid_verification_token'));

				return;
			}
			else if (!$validationData['token'])
			{
				return;
			}

			/** @var \LiamW\XenForoLicenseVerification\Service\LicenseValidator $validationService */
			$validationService = $this->service('LiamW\XenForoLicenseVerification:LicenseValidator', $validationData['token'], $validationData['domain']);

			if ($validationService->validate()->isValid($error))
			{
				$validationService->setDetailsOnUser($this->user);
			}
			else
			{
				$this->user->error($error);
			}
		}
	}

	public function setFromInput(array $input)
	{
		parent::setFromInput($input);

		$this->validateXenForoLicense($input['license_validation']);
	}
}