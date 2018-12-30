<?php

namespace LiamW\XenForoLicenseVerification;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;

/**
 * @property string validation_token
 * @property string customer_token
 * @property string license_token
 * @property boolean can_transfer
 * @property string test_domain
 * @property boolean domain_match
 * @property boolean is_valid
 */
class XFApi
{
	const VALIDATION_URL = "https://xenforo.com/api/license-lookup.json";

	protected $httpClient;
	protected $rawResponse;
	protected $responseCode;
	protected $responseJson;

	protected $token;
	protected $domain;

	public function __construct(Client $httpClient, $token, $domain = null)
	{
		$this->httpClient = $httpClient;

		$this->token = $token;
		$this->domain = $domain;
	}

	/**
	 * @param string $token
	 */
	public function setToken($token)
	{
		$this->token = $token;
	}

	/**
	 * Sets the domain to pass to the XenForo license API. If set to null, no domain will be passed.
	 *
	 * @param string|null $domain
	 */
	public function setDomain($domain)
	{
		$this->domain = $domain;
	}

	public function validate()
	{
		try
		{
			if ($this->isGuzzle6())
			{
				$requestOptions = [
					'form_params' => [
						'token' => $this->token,
						'domain' => $this->domain ?: ''
					]
				];
			}
			else
			{
				$requestOptions = [
					'body' => [
						'token' => $this->token,
						'domain' => $this->domain ?: ''
					]
				];
			}

			$this->rawResponse = $this->httpClient->post(self::VALIDATION_URL, $requestOptions);

			$this->responseCode = $this->rawResponse->getStatusCode();
			$this->responseJson = \json_decode($this->rawResponse->getBody(), true);
		} catch (ClientException $e)
		{
			$this->responseCode = $e->getCode();
		} catch (ServerException $e)
		{
			$this->responseCode = $e->getCode();
		}
	}

	/**
	 * @return int
	 */
	public function getResponseCode()
	{
		return $this->responseCode;
	}

	public function licenseExists()
	{
		return $this->responseCode == 200;
	}

	final public function __get($name)
	{
		return $this->responseJson[$name];
	}

	final public function __isset($name)
	{
		return $this->responseJson && isset($this->responseJson[$name]);
	}

	final public function __set($name, $value)
	{
		throw new \BadMethodCallException("Cannot set values on LicenseValidator");
	}

	final public function __unset($name)
	{
		throw new \BadMethodCallException("Cannot unset values on LicenseValidator");
	}

	protected function isGuzzle6()
	{
		return version_compare(Client::VERSION, '6.0.0', '>=');
	}
}