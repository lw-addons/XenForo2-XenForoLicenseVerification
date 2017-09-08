<?php

namespace LiamW\XenForoLicenseVerification\Service\XenForoLicense;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use XF\Entity\User;
use XF\Service\AbstractService;

/**
 * @property string  validation_token
 * @property string  customer_token
 * @property string  license_token
 * @property boolean can_transfer
 * @property string  test_domain
 * @property boolean domain_match
 * @property boolean is_valid
 */
class Verifier extends AbstractService implements \ArrayAccess
{
	const VALIDATION_URL = "https://xenforo.com/api/license-lookup.json";

	protected $token;
	protected $domain;

	/** @var \GuzzleHttp\Client */
	protected $httpClient;

	protected $responseCode;
	protected $responseJson;

	protected $rawResponse;

	protected $options = [
		'requireUniqueCustomer' => null,
		'requireUniqueLicense' => null,
		'licensedUsergroup' => null,
		'licensedUsergroupPrimary' => null,
		'transferableUsergroup' => null,
		'checkDomain' => null,
		'recheckUserId' => null
	];

	protected $errors = [];

	public function __construct(\XF\App $app, $token, $domain = null, array $options = [])
	{
		$this->token = $token;
		$this->domain = $domain;

		$this->options = array_merge($this->options, $options);

		parent::__construct($app);
	}

	public function getRaw()
	{
		return $this->responseJson;
	}

	protected function setup()
	{
		$this->httpClient = $this->app->http()->client();

		$this->processOptionDefaults();

		if (!$this->token || strlen($this->token) != 32 || !preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $this->token))
		{
			$this->errors[] = \XF::phraseDeferred('liamw_xenforolicenseverification_invalid_verification_token');
		}

		if ($this->options['checkDomain'] && !$this->domain)
		{
			$this->errors[] = \XF::phraseDeferred('liamw_xenforolicenseverification_invalid_domain');
		}
	}

	protected function processOptionDefaults()
	{
		if ($this->options['requireUniqueCustomer'] === null)
		{
			$this->options['requireUniqueCustomer'] = $this->app->options()->liamw_xenforolicenseverification_unique_customer;
		}

		if ($this->options['requireUniqueLicense'] === null)
		{
			$this->options['requireUniqueLicense'] = $this->app->options()->liamw_xenforolicenseverification_unique_license;
		}

		if ($this->options['checkDomain'] === null)
		{
			$this->options['checkDomain'] = $this->app->options()->liamw_xenforolicenseverification_check_domain;
		}

		if ($this->options['licensedUsergroup'] === null)
		{
			$this->options['licensedUsergroup'] = $this->app->options()->liamw_xenforolicenseverification_licensed_group;
		}

		if ($this->options['licensedUsergroupPrimary'] === null)
		{
			$this->options['licensedUsergroupPrimary'] = $this->app->options()->liamw_xenforolicenseverification_licensed_primary;
		}

		if ($this->options['transferableUsergroup'] === null)
		{
			$this->options['transferableUsergroup'] = $this->app->options()->liamw_xenforolicenseverification_transfer_group;
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
			$error = \XF::phraseDeferred('liamw_xenforolicenseverification_license_not_found');

			return false;
		}

		if (!$this->licenseValid())
		{
			$error = \XF::phraseDeferred('liamw_xenforolicenseverification_license_not_valid');

			return false;
		}

		if ($this->options['checkDomain'] && !$this->domainMatches())
		{
			$error = \XF::phraseDeferred('liamw_xenforolicenseverification_domain_not_match_license');

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
				$error = \XF::phraseDeferred('liamw_xenforolicenseverification_license_token_not_unique');

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
				$error = \XF::phraseDeferred('liamw_xenforolicenseverification_customer_token_not_unique');

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
			'validation_date' => \XF::$time
		]);

		if ($this->options['licensedUsergroup'] && $this->options['licensedUsergroupPrimary'])
		{
			$user->user_group_id = $this->options['licensedUsergroup'];
		}

		if ($saveUser)
		{
			$user->save();
		}

		\XF::runLater(function () use ($user) {
			if ($this->options['licensedUsergroup'] && !$this->options['licensedUsergroupPrimary'])
			{
				\XF::app()->service('XF:User\UserGroupChange')
					->addUserGroupChange($user->user_id, 'xfLicenseValid', $this->options['licensedUsergroup']);
			}

			if ($this->options['transferableUsergroup'] && $this->can_transfer)
			{
				\XF::app()->service('XF:User\UserGroupChange')
					->addUserGroupChange($user->user_id, 'xfLicenseTransferable', $this->options['transferableUsergroup']);
			}
		});
	}

	public function licenseExists()
	{
		return $this->responseCode == 200;
	}

	public function licenseValid()
	{
		return $this->is_valid;
	}

	public function domainMatches()
	{
		return $this->domain_match;
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
		throw new \BadMethodCallException("Cannot set values on LicenseValidator");
	}

	public function offsetUnset($offset)
	{
		throw new \BadMethodCallException("Cannot unset values on LicenseValidator");
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