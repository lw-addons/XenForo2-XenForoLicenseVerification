<?php

namespace LiamW\XenForoLicenseVerification;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Alter;
use XF\Db\Schema\Create;
use XF\Entity\CronEntry;

class Setup extends AbstractSetup
{
	use StepRunnerUpgradeTrait;

	public function install(array $stepParams = [])
	{
		$this->schemaManager()->createTable('xf_liamw_xenforo_license_data', function(Create $table)
		{
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

	public function upgrade10106Step1()
	{
		$this->schemaManager()->alterTable('xf_user', function(Alter $table)
		{
			$table->addColumn('api_customer_token', 'varchar', 32)->nullable();
		});
	}

	public function upgrade10202Step1()
	{
		$this->schemaManager()->alterTable('xf_user', function(Alter $table)
		{
			$table->addColumn('api_license_token', 'varchar', 32)->nullable();
			$table->addColumn('api_license_valid', 'bool')->nullable();
		});
	}

	public function upgrade20000Step1()
	{
		$this->schemaManager()->alterTable('xf_user_profile', function(Alter $table)
		{
			$table->addColumn('xenforo_api_validation_token', 'varchar', 32)->nullable();
			$table->addColumn('xenforo_api_validation_domain', 'varchar', 255)->nullable();
			$table->addColumn('xenforo_api_last_check', 'int')->nullable();
			$table->addColumn('xenforo_api_customer_token', 'varchar', 32)->nullable();
			$table->addColumn('xenforo_api_license_token', 'varchar', 32)->nullable();
			$table->addColumn('xenforo_api_license_valid', 'bool')->nullable();
		});
	}

	public function upgrade20000Step2()
	{
		$this->query("
			UPDATE
    			xf_user_profile
		  	INNER JOIN (
				SELECT
					user_id,
					api_key,
					api_domain,
					api_expiry,
					api_customer_token,
					api_license_token,
					api_license_valid
				FROM xf_user
				GROUP BY user_id
			) xf_user ON xf_user_profile.user_id = xf_user.user_id
			SET
				xf_user_profile.xenforo_api_validation_token = xf_user.api_key,
				xf_user_profile.xenforo_api_validation_domain = xf_user.api_domain,
				xf_user_profile.xenforo_api_last_check = IF(xf_user.api_expiry - ? < 0, 0, xf_user.api_expiry - ?),
				xf_user_profile.xenforo_api_customer_token = xf_user.api_customer_token,
				xf_user_profile.xenforo_api_license_token = xf_user.api_license_token,
				xf_user_profile.xenforo_api_license_valid = xf_user.api_license_valid
		");
	}

	public function upgrade20000Step3()
	{
		$this->schemaManager()->alterTable('xf_user', function(Alter $table)
		{
			$table->dropColumns([
				'api_key',
				'api_domain',
				'api_expiry',
				'api_customer_token',
				'api_license_token',
				'api_license_valid'
			]);
		});
	}

	public function upgrade3000031Step1()
	{
		$this->schemaManager()->createTable('xf_liamw_xenforo_license_data', function(Create $table)
		{
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

	public function upgrade3000031Step2()
	{
		$this->db()
			->query("INSERT INTO xf_liamw_xenforo_license_data(user_id, validation_token, customer_token, license_token, domain, domain_match, can_transfer, validation_date) SELECT user_id, xenforo_api_validation_token, xenforo_api_customer_token, xenforo_api_license_token, xenforo_api_validation_domain, NULL, 0, xenforo_api_last_check FROM xf_user_profile WHERE xenforo_api_validation_token IS NOT NULL");
	}

	public function upgrade3000031Step3()
	{
		$this->schemaManager()->alterTable('xf_user_profile', function(Alter $table)
		{
			$table->dropColumns([
				'xenforo_api_validation_token',
				'xenforo_api_validation_domain',
				'xenforo_api_last_check',
				'xenforo_api_customer_token',
				'xenforo_api_license_token',
				'xenforo_api_license_valid'
			]);
		});
	}

	public function uninstall(array $stepParams = [])
	{
		$this->schemaManager()->dropTable('xf_liamw_xenforo_license_data');
	}

	public function postInstall(array &$stateChanges)
	{
		$this->randomiseCronTime();
	}

	public function postUpgrade($previousVersion, array &$stateChanges)
	{
		$this->randomiseCronTime();
	}

	public function onActiveChange($newActive, array &$jobList)
	{
		// Only randomise when enabled
		if ($newActive)
		{
			$this->randomiseCronTime();
		}
	}

	protected function randomiseCronTime()
	{
		/** @var CronEntry $recheckCron */
		$recheckCron = $this->app()->find('XF:CronEntry', 'liamw_xenforolicenseexpir');

		if ($recheckCron)
		{
			$runRules = $recheckCron->run_rules;
			$runRules['hours'] = [mt_rand(0, 23)];
			$runRules['minutes'] = [mt_rand(0, 59)];

			$recheckCron->run_rules = $runRules;
			$recheckCron->save();
		}
	}
}