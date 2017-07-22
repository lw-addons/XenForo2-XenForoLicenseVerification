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

		if (\XF::visitor()->user_state != 'valid' || \XF::visitor()->xf_customer_token)
		{
			return $this->noPermission();
		}

		$input = $this->filter([
			'license_validation' => [
				'token' => 'str',
				'domain' => 'str'
			]
		]);

		/** @var \LiamW\XenForoLicenseVerification\Service\LicenseValidator $validationService */
		$validationService = $this->service('LiamW\XenForoLicenseVerification:LicenseValidator', $input['license_validation']['token'], $input['license_validation']['domain']);

		if (!$validationService->validate()->isValid($error))
		{
			return $this->error($error);
		}
		else
		{
			$validationService->setDetailsOnUser(\XF::visitor());
			\XF::visitor()->save();

			return $this->redirect($this->buildLink('account/xenforo-license'));
		}
	}
}