<?php

namespace LiamW\XenForoLicenseVerification\XF\Api\Controller;

use XF\Mvc\Entity\Entity;
use XF\Mvc\ParameterBag;

class User extends XFCP_User
{
	public function actionGetXenForoLicense(ParameterBag $params)
	{
		$user = $this->assertViewableUser($params->user_id);

		$result = [
			'XenForoLicense' => $user->XenForoLicense ? $user->XenForoLicense->toApiResult(Entity::VERBOSITY_VERBOSE) : null
		];

		return $this->apiResult($result);
	}

	public function actionDeleteXenForoLicense(ParameterBag $params)
	{
		$user = $this->assertViewableUser($params->user_id);
		$user->XenForoLicense->delete();

		return $this->apiSuccess();
	}

	public function actionPostXenForoLicense(ParameterBag $params)
	{
		$user = $this->assertViewableUser($params->user_id);

		if ($user->user_id != \XF::visitor()->user_id)
		{
			return $this->noPermission();
		}

	}
}