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
		'requireUniqueCustomer' => false
	];

	public function __construct(\XF\App $app, $token, $domain = null, array $options = [])
	{
		parent::__construct($app);

		$this->token = $token;
		$this->domain = $domain;

		$this->options = array_merge($this->options, $options);
	}

	protected function setup()
	{
		$this->checkDomain = $this->domain != null;
		$this->httpClient = $this->app->http()->client();
	}

	/**
	 * @return $this
	 *
	 * @throws \GuzzleHttp\Exception\ServerException
	 */
	public function validate()
	{
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

	public function isValid($domainMatch = null, &$error = '')
	{
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

		if ($domainMatch === null)
		{
			$domainMatch = isset($this->domain);
		}

		if ($domainMatch && !$this->domainMatches())
		{
			$error = \XF::phraseDeferred('liamw_xenforolicensevalidation_domain_not_match_license');

			return false;
		}

		if ($this->options['requireUniqueCustomer'])
		{
			$existingUsers = $this->finder('XF:User')->where('xf_customer_token', $this->customer_token)->fetch();

			if ($existingUsers->count())
			{
				$error = \XF::phraseDeferred('liamw_xenforolicensevalidation_customer_token_not_unique');

				return false;
			}
		}

		return true;
	}

	public function setDetailsOnUser(User $user)
	{
		if (!$this->isValid())
		{
			throw new \BadMethodCallException("Cannot set details on user when license isn't valid.");
		}

		$user->bulkSet([
			'xf_customer_token' => $this->customer_token,
			'xf_validation_date' => \XF::$time
		], [
			'forceSet' => true
		]);

		\XF::runLater(function () use ($user)
		{
			if (\XF::options()->liamw_xenforolicensevalidation_add_usergroup)
			{
				\XF::app()->service('XF:User\UserGroupChange')
					->addUserGroupChange($user->user_id, 'xfLicenseValid', \XF::options()->liamw_xenforolicensevalidation_add_usergroup);
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
		throw new \Exception("Cannot set values on the LicenseValidator");
	}

	public function offsetUnset($offset)
	{
		unset($this->responseJson[$offset]);
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