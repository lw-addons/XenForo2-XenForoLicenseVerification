<?php

namespace LiamW\XenForoLicenseVerification\XF\Searcher;

class User extends XFCP_User
{
	protected function init()
	{
		parent::init();

		$this->allowedRelations[] = 'XenForoLicense';
	}
}