<?php

namespace LiamW\XenForoLicenseVerification;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Alter;

class Setup extends AbstractSetup
{
	use StepRunnerInstallTrait;
	use StepRunnerUpgradeTrait;
	use StepRunnerUninstallTrait;

	public function installStep1()
	{
		$this->schemaManager()->alterTable('xf_user', function (Alter $table)
		{
			$table->addColumn('xf_customer_token', 'varchar', 50)->nullable()
				->comment("Added by XenForo License Validation");
			$table->addColumn('xf_validation_date', 'int')->setDefault(0)
				->comment("Added by XenForo License Validation");
		});
	}
}