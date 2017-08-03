<?php

namespace LiamW\XenForoLicenseVerification\Job;

use XF\Job\AbstractJob;

class LicenseValidationExpiry extends AbstractJob
{
	protected $defaultData = [
		'start' => 0,
		'batch' => 100
	];

	public function run($maxRunTime)
	{
		$startTime = microtime(true);

		$validationCutoff = \XF::$time - (\XF::app()->options()->liamw_xenforolicenseverification_cutoff * 24 * 60 * 60);

		$expiredUsers = \XF::app()->finder('XF:User')->where('user_id', '>', $this->data['start'])
			->where('XenForoLicense.validation_date', '<=', $validationCutoff)
			->fetch($this->data['batch']);

		if (!$expiredUsers->count())
		{
			return $this->complete();
		}

		$options = \XF::app()->options();

		$recheck = $options->liamw_xenforolicenseverification_auto_recheck;

		$done = 0;

		/** @var \XF\Entity\User $expiredUser */
		foreach ($expiredUsers AS $expiredUser)
		{
			if (microtime(true) - $startTime >= $maxRunTime)
			{
				break;
			}

			$this->data['start'] = $expiredUser->user_id;

			\XF::db()->beginTransaction();

			if ($recheck && $expiredUser->XenForoLicense->validation_token)
			{
				/** @var \LiamW\XenForoLicenseVerification\Service\XenForoLicenseVerifier $validationService */
				$validationService = \XF::service('LiamW\XenForoLicenseVerification:LicenseValidator', $expiredUser->XenForoLicense->validation_token, $expiredUser->XenForoLicense->domain, [
					'recheckUserId' => $expiredUser->user_id
				]);

				if ($validationService->validate()->isValid($error))
				{
					$validationService->setDetailsOnUser($expiredUser, true);

					continue;
				}
			}

			$expiredUser->XenForoLicense->delete();

			\XF::app()->service('XF:User\UserGroupChange')
				->removeUserGroupChange($expiredUser->user_id, 'xfLicenseValid');
			\XF::app()->service('XF:User\UserGroupChange')
				->removeUserGroupChange($expiredUser->user_id, 'xfLicenseTransferable');

			/** @var \XF\Repository\UserAlert $alertRepo */
			$alertRepo = \XF::repository('XF:UserAlert');
			$alertRepo->alert($expiredUser, 0, '', 'user', $expiredUser->user_id, 'xflicenseverification_lapsed');

			\XF::db()->commit();

			$done++;
		}

		$this->data['batch'] = $this->calculateOptimalBatch($this->data['batch'], $done, $startTime, $maxRunTime, 1000);

		return $this->resume();
	}

	public function getStatusMessage()
	{
		$actionPhrase = \XF::phrase('rebuilding');
		$typePhrase = \XF::phrase('liamw_xenforolicenseverification_xenforo_license');

		return sprintf('%s... %s (%s)', $actionPhrase, $typePhrase, $this->data['start']);
	}

	public function canCancel()
	{
		return false;
	}

	public function canTriggerByChoice()
	{
		return true;
	}
}