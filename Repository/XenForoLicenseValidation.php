<?php

namespace LiamW\XenForoLicenseVerification\Repository;

use XF\Entity\User;
use XF\Mvc\Entity\Repository;

class XenForoLicenseValidation extends Repository
{
	public function expireValidation(User $expiredUser)
	{
		$expiredUser->XenForoLicense->delete();

		if ($this->app()->options()->liamw_xenforolicenseverification_licensed_primary)
		{
			$expiredUser->user_group_id = 2;
			$expiredUser->save();
		}

		\XF::app()->service('XF:User\UserGroupChange')
			->removeUserGroupChange($expiredUser->user_id, 'xfLicenseValid');
		\XF::app()->service('XF:User\UserGroupChange')
			->removeUserGroupChange($expiredUser->user_id, 'xfLicenseTransferable');

		/** @var \XF\Repository\UserAlert $alertRepo */
		$alertRepo = \XF::repository('XF:UserAlert');
		$alertRepo->alert($expiredUser, 0, '', 'user', $expiredUser->user_id, 'xflicenseverification_lapsed');
	}
}