<?php

namespace LiamW\XenForoLicenseVerification\Repository;

use XF\Entity\User;
use XF\Mvc\Entity\Repository;

class XenForoLicenseValidation extends Repository
{
	public function expireValidation(User $user, $removeCustomerToken = null, $sendAlert = true)
	{
		if ($removeCustomerToken === null)
		{
			$removeCustomerToken = !\XF::options()->liamw_xenforolicenseverification_maintain_customer;
		}

		$user->XenForoLicense->deleteLicenseData($removeCustomerToken);

		if ($this->app()->options()->liamw_xenforolicenseverification_licensed_primary)
		{
			$user->user_group_id = 2;
			$user->save();
		}

		\XF::app()->service('XF:User\UserGroupChange')
			->removeUserGroupChange($user->user_id, 'xfLicenseValid');
		\XF::app()->service('XF:User\UserGroupChange')
			->removeUserGroupChange($user->user_id, 'xfLicenseTransferable');

		if ($sendAlert)
		{
			/** @var \XF\Repository\UserAlert $alertRepo */
			$alertRepo = \XF::repository('XF:UserAlert');
			$alertRepo->alert($user, $user->user_id, $user->username, 'user', $user->user_id, 'xflicenseverification_lapsed');
		}
	}
}