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

		$validationCutoff = \XF::$time - (\XF::app()
					->options()->liamw_xenforolicenseverification_cutoff * 24 * 60 * 60);

		$expiredUsers = \XF::app()->finder('XF:User')->where('user_id', '>', $this->data['start'])
			->where('XenForoLicense.validation_date', '<=', $validationCutoff)
			->fetch($this->data['batch']);

		if (!$expiredUsers->count())
		{
			return $this->complete();
		}

		$recheck = \XF::app()->options()->liamw_xenforolicenseverification_auto_recheck;

		$done = 0;

		/** @var \XF\Entity\User $expiredUser */
		foreach ($expiredUsers AS $expiredUser)
		{
			$done++;

			$this->data['start'] = $expiredUser->user_id;

			\XF::db()->beginTransaction();

			if ($recheck && $expiredUser->XenForoLicense->validation_token)
			{
				/** @var \LiamW\XenForoLicenseVerification\Service\XenForoLicense\Verifier $validationService */
				$validationService = \XF::service('LiamW\XenForoLicenseVerification:XenForoLicense\Verifier', $expiredUser, $expiredUser->XenForoLicense->validation_token, $expiredUser->XenForoLicense->domain);

				if ($validationService->isValid($error))
				{
					$validationService->applyLicense(true);
					continue;
				}
			}

			\XF::repository('LiamW\XenForoLicenseVerification:XenForoLicenseValidation')
				->expireValidation($expiredUser);

			\XF::db()->commit();

			if (microtime(true) - $startTime >= $maxRunTime)
			{
				break;
			}
		}

		$this->data['batch'] = $this->calculateOptimalBatch($this->data['batch'], $done, $startTime, $maxRunTime, 1000);

		return $this->resume();
	}

	public function getStatusMessage()
	{
		$actionPhrase = \XF::phrase('renewing');
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