<?php

namespace LiamW\XenForoLicenseVerification\XF\Service\User;

class Registration extends XFCP_Registration
{
	// Ugly field name, but it'll prevent clashes.
	protected $liamw_xenforolicenseverification_xenforo_license_data;

	protected function verifyXenForoLicense(array $verificationData)
	{
		if ($this->app->options()->liamw_xenforolicenseverification_registration['request'])
		{
			if ($this->app->options()->liamw_xenforolicenseverification_registration['require'] && !$verificationData['token'])
			{
				$this->user->error(\XF::phrase('liamw_xenforolicenseverification_please_enter_a_valid_xenforo_license_validation_token'));

				return;
			}
			else if (!$verificationData['token'])
			{
				return;
			}

			/** @var \LiamW\XenForoLicenseVerification\Service\XenForoLicense\Verifier $verificationService */
			$verificationService = $this->service('LiamW\XenForoLicenseVerification:XenForoLicense\Verifier', $this->user, $verificationData['token'], $verificationData['domain']);

			if ($verificationService->isValid($error))
			{
				$verificationService->applyLicense(false);
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

		$this->liamw_xenforolicenseverification_xenforo_license_data = $input['xenforo_license_verification'];
	}

	protected function finalSetup()
	{
		parent::finalSetup();

		$this->verifyXenForoLicense($this->liamw_xenforolicenseverification_xenforo_license_data);
	}
}