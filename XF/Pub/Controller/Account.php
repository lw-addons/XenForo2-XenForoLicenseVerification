<?php

namespace LiamW\XenForoLicenseVerification\XF\Pub\Controller;

class Account extends XFCP_Account
{
	public function actionXenForoLicense()
	{
		if (\XF::visitor()->user_state != 'valid')
		{
			return $this->noPermission();
		}

		$view = $this->view('LiamW\XenForoLicenseVerification:Account\XenForoLicense', 'liamw_xenforolicenseverification_verify_license');

		return $this->addAccountWrapperParams($view, 'liamw_xenforo_license');
	}

	public function actionXenForoLicenseProcess()
	{
		$this->assertPostOnly();

		if (\XF::visitor()->user_state != 'valid' || \XF::visitor()->XenForoLicense)
		{
			return $this->noPermission();
		}

		$input = $this->filter([
			'xenforo_license_verification' => [
				'token' => 'str',
				'domain' => 'str'
			]
		]);

		/** @var \LiamW\XenForoLicenseVerification\Service\XenForoLicenseVerifier $verificationService */
		$verificationService = $this->service('LiamW\XenForoLicenseVerification:XenForoLicenseVerifier', $input['xenforo_license_verification']['token'], $input['xenforo_license_verification']['domain']);

		if (!$verificationService->validate()->isValid($error))
		{
			return $this->error($error);
		}
		else
		{
			$verificationService->setDetailsOnUser(\XF::visitor());
			\XF::visitor()->save();

			return $this->redirect($this->buildLink('account/xenforo-license'));
		}
	}
}