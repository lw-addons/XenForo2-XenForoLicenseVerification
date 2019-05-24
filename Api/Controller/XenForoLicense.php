<?php

namespace LiamW\XenForoLicenseVerification\Api\Controller;

use XF\Api\Controller\AbstractController;
use XF\Mvc\Entity\Entity;
use XF\Mvc\ParameterBag;

/**
 * @api-group Users
 */
class XenForoLicense extends AbstractController
{
	/**
	 * @api-route users/{id}/xenforo-license
	 *
	 * @api-desc Gets the XenForo license details for the specified user.
	 *
	 * @api-out XenForoLicense $xenforoLicense
	 */
	public function actionGetXenForoLicense(ParameterBag $params)
	{
		$this->assertApiScopeByRequestMethod('user:xenforo_license');

		$user = $this->assertViewableUser($params->user_id);

		$result = [
			'xenforoLicense' => $user->XenForoLicense ? $user->XenForoLicense->toApiResult(Entity::VERBOSITY_VERBOSE) : null
		];

		return $this->apiResult($result);
	}

	/**
	 * @api-route users/{id}/xenforo-license
	 *
	 * @api-desc Verify a users XenForo license.
	 *
	 * @api-out true $success
	 * @api-out User $user
	 */
	public function actionPostXenForoLicense(ParameterBag $params)
	{
		$this->assertApiScopeByRequestMethod('user:xenforo_license');

		$this->assertRegisteredUser();

		$user = $this->assertViewableUser($params->user_id);

		if ($user->user_id != \XF::visitor()->user_id)
		{
			$this->noPermission();
		}

		$input = $this->filter([
			'xenforo_license_verification' => [
				'token' => 'str',
				'domain' => 'str'
			]
		]);

		/** @var \LiamW\XenForoLicenseVerification\Service\XenForoLicense\Verifier $verificationService */
		$verificationService = $this->service('LiamW\XenForoLicenseVerification:XenForoLicense\Verifier', $input['xenforo_license_verification']['token'], $input['xenforo_license_verification']['domain']);

		if ($verificationService->isValid($error))
		{
			$verificationService->applyLicenseData($user);
			$user->save();

			return $this->apiSuccess(['user' => $user->toApiResult(Entity::VERBOSITY_QUIET)]);
		}
		else
		{
			return $this->error($error);
		}
	}

	/**
	 * @api-route users/{id}/xenforo-license
	 *
	 * @api-desc Delete a users XenForo license data.
	 *
	 * @api-in bool $remove_customer_token If specified, the customer token will also be removed, regardless of option.
	 *
	 * @api-out true $success
	 */
	public function actionDeleteXenForoLicense(ParameterBag $params)
	{
		$this->assertApiScopeByRequestMethod('user:xenforo_license');

		$user = $this->assertViewableUser($params->user_id);

		$removeCustomerToken = \XF::visitor()->hasAdminPermission('user') && $this->filter('remove_customer_token', 'bool');

		$user->XenForoLicense->deleteLicenseData($removeCustomerToken);

		return $this->apiSuccess();
	}

	/**
	 * @param int $id
	 * @param mixed $with
	 * @param bool $basicProfileOnly
	 *
	 * @return \XF\Entity\User
	 *
	 * @throws \XF\Mvc\Reply\Exception
	 */
	protected function assertViewableUser($id, $with = 'api', $basicProfileOnly = true)
	{
		/** @var \XF\Entity\User $user */
		$user = $this->assertRecordExists('XF:User', $id, $with);

		if (\XF::isApiCheckingPermissions())
		{
			$canView = $basicProfileOnly ? $user->canViewBasicProfile($error) : $user->canViewFullProfile($error);
			if (!$canView)
			{
				throw $this->exception($this->noPermission($error));
			}
		}

		return $user;
	}
}