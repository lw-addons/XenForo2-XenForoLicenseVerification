<?php

namespace LiamW\XenForoLicenseVerification;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Alter;
use XF\Db\Schema\Create;

class Setup extends AbstractSetup
{
	use StepRunnerInstallTrait;
	use StepRunnerUpgradeTrait;
	use StepRunnerUninstallTrait;

	public function installStep1()
	{
		$this->schemaManager()->createTable('xf_liamw_xenforo_license_data', function (Create $table) {
			$table->addColumn('user_id', 'int')->primaryKey();
			$table->addColumn('validation_token', 'varchar', 50);
			$table->addColumn('customer_token', 'varchar', 50);
			$table->addColumn('license_token', 'varchar', 50);
			$table->addColumn('domain', 'varchar', 255)->nullable();
			$table->addColumn('domain_match', 'bool')->nullable();
			$table->addColumn('can_transfer', 'bool');
			$table->addColumn('validation_date', 'int');
		});
	}

	public function upgrade2000070Step1()
	{

	}

	public function uninstallStep1()
	{
		$this->schemaManager()->dropTable('xf_liamw_xenforo_license_data');
	}
}