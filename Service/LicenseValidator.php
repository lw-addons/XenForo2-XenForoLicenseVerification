<?php

namespace LiamW\XenForoLicenseVerification\Service;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use XF\Entity\User;
use XF\Service\AbstractService;

class LicenseValidator extends AbstractService implements \ArrayAccess
{
	const VALIDATION_URL = "https://xenforo.com/api/license-lookup.json";

	protected $token;
	protected $domain;

	protected $checkDomain;

	/** @var \GuzzleHttp\Client */
	protected $httpClient;

	protected $responseCode;
	protected $responseJson;

	protected $rawResponse;

	protected $options = [
		'requireUniqueCustomer' => false,
		'requireUniqueLicense' => false,
		'checkDomain' => true,
		'recheckUserId' => null
	];

	protected $errors = [];

	public function __construct(\XF\App $app, $token, $domain = null, array $options = [])
	{
		parent::__construct($app);

		$this->token = $token;
		$this->domain = $domain;

		$this->options = array_merge($this->options, $options);
	}

	public function getRaw()
	{
		return $this->responseJson;
	}

	protected function setup()
	{
		$this->checkDomain = $this->domain != null;
		$this->httpClient = $this->app->http()->client();

		if (!$this->token || strlen($this->token) != 32 || !preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $this->token))
		{
			$errors[] = \XF::phraseDeferred('liamw_xenforolicenseverification_invalid_verification_token');
		}

		if ($this->options['checkDomain'] && !$this->domain)
		{
			$errors[] = \XF::phraseDeferred('liamw_xenforolicenseverification_invalid_domain');
		}
	}

	/**
	 * @return $this
	 *
	 * @throws \GuzzleHttp\Exception\ServerException
	 */
	public function validate()
	{
		if ($this->errors)
		{
			return $this;
		}

		try
		{
			$this->rawResponse = $this->httpClient->post(self::VALIDATION_URL, [
				'body' => [
					'token' => $this->token,
					'domain' => $this->domain ?: ''
				]
			]);

			$this->responseCode = $this->rawResponse->getStatusCode();
			$this->responseJson = $this->rawResponse->json();
		} catch (ClientException $e)
		{
			$this->responseCode = $e->getCode();
		} catch (ServerException $e)
		{
			$this->responseCode = $e->getCode();
		}

		return $this;
	}

	public function isValid(&$error = '')
	{
		if ($this->errors)
		{
			$error = reset($this->errors);

			return false;
		}

		if (!$this->licenseExists())
		{
			$error = \XF::phraseDeferred('liamw_xenforolicensevalidation_license_not_found');

			return false;
		}

		if (!$this->licenseValid())
		{
			$error = \XF::phraseDeferred('liamw_xenforolicensevalidation_license_not_valid');

			return false;
		}

		if ($this->options['checkDomain'] && !$this->domainMatches())
		{
			$error = \XF::phraseDeferred('liamw_xenforolicenseverification_invalid_domain');

			return false;
		}

		if ($this->options['requireUniqueLicense'])
		{
			$existingUsersFinder = $this->finder('XF:User')
				->where('XenForoLicense.license_token', $this->license_token);

			if ($this->options['recheckUserId'])
			{
				$existingUsersFinder->where('user_id', '!=', $this->options['recheckUserId']);
			}

			$existingUsers = $existingUsersFinder->fetch();

			if ($existingUsers->count())
			{
				$error = \XF::phraseDeferred('liamw_xenforolicensevalidation_license_token_not_unique');

				return false;
			}
		}

		if ($this->options['requireUniqueCustomer'])
		{
			$existingUsersFinder = $this->finder('XF:User')
				->where('XenForoLicense.customer_token', $this->license_token);

			if ($this->options['recheckUserId'])
			{
				$existingUsersFinder->where('user_id', '!=', $this->options['recheckUserId']);
			}

			$existingUsers = $existingUsersFinder->fetch();

			if ($existingUsers->count())
			{
				$error = \XF::phraseDeferred('liamw_xenforolicensevalidation_customer_token_not_unique');

				return false;
			}
		}

		return true;
	}

	public function setDetailsOnUser(User $user, $saveUser = false)
	{
		if (!$this->isValid())
		{
			throw new \BadMethodCallException("Cannot set details on user when license isn't valid.");
		}

		$licenseData = $user->getRelationOrDefault('XenForoLicense');
		$licenseData->bulkSet([
			'validation_token' => $this->validation_token,
			'customer_token' => $this->customer_token,
			'license_token' => $this->license_token,
			'can_transfer' => $this->can_transfer,
			'domain' => $this->test_domain,
			'domain_match' => $this->domain_match,
			'check_date' => \XF::$time
		]);

		if ($saveUser)
		{
			$user->save();
		}

		\XF::runLater(function () use ($user)
		{
			if (\XF::options()->liamw_xenforolicensevalidation_licensed_usergroup)
			{
				\XF::app()->service('XF:User\UserGroupChange')
					->addUserGroupChange($user->user_id, 'xfLicenseValid', \XF::options()->liamw_xenforolicensevalidation_licensed_usergroup);
			}

			if (\XF::options()->liamw_xenforolicensevalidation_transferable_group && $this->can_transfer)
			{
				\XF::app()->service('XF:User\UserGroupChange')
					->addUserGroupChange($user->user_id, 'xfLicenseTransferable', \XF::options()->liamw_xenforolicensevalidation_transferable_group);
			}
		});
	}

	public function licenseExists()
	{
		return $this->responseCode == 200;
	}

	public function licenseValid()
	{
		return $this->responseJson['is_valid'];
	}

	public function domainMatches()
	{
		return $this->responseJson['domain_match'];
	}

	public function offsetExists($offset)
	{
		return $this->responseJson && isset($this->responseJson[$offset]);
	}

	public function offsetGet($offset)
	{
		return $this->responseJson[$offset];
	}

	public function offsetSet($offset, $value)
	{
		throw new \BadMethodCallException("Cannot set values on the LicenseValidator");
	}

	public function offsetUnset($offset)
	{
		throw new \BadMethodCallException("Cannot unset values on the LicenseValidator");
	}

	function __get($name)
	{
		return $this->offsetGet($name);
	}

	function __isset($name)
	{
		return $this->offsetExists($name);
	}
}