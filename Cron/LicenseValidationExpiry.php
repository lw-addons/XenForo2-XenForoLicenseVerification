<?php

namespace LiamW\XenForoLicenseVerification\Cron;

class LicenseValidationExpiry
{
	public static function run()
	{
		\XF::app()->jobManager()
			->enqueueUnique('liamw_xenforolicenseverification', '\LiamW\XenForoLicenseVerification:LicenseValidationExpiry', [], false);
	}
}