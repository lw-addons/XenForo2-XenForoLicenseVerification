<?php

namespace LiamW\XenForoLicenseVerification\XF\Service\User;

class Registration extends XFCP_Registration
{
	protected function verifyXenForoLicense(array $verificationData)
	{
		if ($this->app->options()->liamw_xenforolicenseverification_registration['request'])
		{
			if ($this->app->options()->liamw_xenforolicenseverification_registration['require'] && !$verificationData['token'])
			{
				$this->user->error(\XF::phrase('liamw_xenforolicenseverification_invalid_verification_token'));

				return;
			}
			else if (!$verificationData['token'])
			{
				return;
			}

			/** @var \LiamW\XenForoLicenseVerification\Service\XenForoLicense\Verifier $verificationService */
			$verificationService = $this->service('LiamW\XenForoLicenseVerification:XenForoLicense\Verifier', $verificationData['token'], $verificationData['domain']);

			if ($verificationService->validate()->isValid($error))
			{
				$verificationService->setDetailsOnUser($this->user);
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

		$this->verifyXenForoLicense($input['xenforo_license_verification']);
	}
}