<?php

namespace LiamW\XenForoLicenseVerification\Cron;

class LicenseValidationExpiry
{
	public static function run()
	{
		$validationCutoff = \XF::$time - (\XF::app()->options()->liamw_xenforolicensevalidation_cutoff * 24 * 60 * 60);

		$expiredUsers = \XF::app()->finder('XF:User')->where('xf_validation_date', '<=', $validationCutoff)->fetch();

		/** @var \XF\Entity\User $expiredUser */
		foreach ($expiredUsers AS $expiredUser)
		{
			$expiredUser->xf_customer_token = null;
			$expiredUser->xf_validation_date = null;

			\XF::app()->service('XF:User\UserGroupChange')
				->removeUserGroupChange($expiredUser->user_id, 'xfLicenseValid');

			$expiredUser->save();
		}
	}
}