<?php

namespace LiamW\XenForoLicenseVerification\Cron;

class LicenseValidationExpiry
{
	public static function run()
	{
		$validationCutoff = \XF::$time - (\XF::app()->options()->liamw_xenforolicensevalidation_cutoff * 24 * 60 * 60);

		$expiredUsers = \XF::app()->finder('XF:User')->where('XenForoLicense.check_date', '<=', $validationCutoff)
			->fetch();

		/** @var \XF\Entity\User $expiredUser */
		foreach ($expiredUsers AS $expiredUser)
		{
			$expiredUser->XenForoLicense->delete();

			\XF::app()->service('XF:User\UserGroupChange')
				->removeUserGroupChange($expiredUser->user_id, 'xfLicenseValid');
			\XF::app()->service('XF:User\UserGroupChange')
				->removeUserGroupChange($expiredUser->user_id, 'xfLicenseTransferable');

			/** @var \XF\Repository\UserAlert $alertRepo */
			$alertRepo = \XF::repository('XF:UserAlert');
			$alertRepo->alert($expiredUser, 0, '', 'user', $expiredUser->user_id, 'xflicenseverification_lapsed');
		}
	}
}