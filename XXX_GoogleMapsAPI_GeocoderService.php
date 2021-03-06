<?php

class XXX_GoogleMapsAPI_GeocoderService
{
	// Free
	public static $serverKey = '';
	
	// Business
	public static $client_ID = '';
	public static $cryptoKey = '';
	
	public static $httpsOnly = false;
	
	public static $authenticationType = 'free';
	
	public static $error = false;
	
	public static function lookupAddress ($rawAddressString = '', $languageCode = 'en', $locationBias = '')
	{
		$result = false;
		
		self::$error = false;
		
		// http://maps.googleapis.com/maps/api/geocode/json?address=1600+Amphitheatre+Parkway,+Mountain+View,+CA&sensor=true_or_false
		
		if (self::$httpsOnly)
		{
			$protocol = 'https://';
		}
		else
		{	
			$protocol = 'http://';
			
			if (class_exists('XXX_HTTPServer') && XXX_HTTPServer::$encryptedConnection)
			{
				$protocol = 'https://';
			}
		}
		
		$domain = 'maps.googleapis.com';
		$path = '/maps/api/geocode/json';
		$path .= '?';
		$path .= 'address=' . urlencode($rawAddressString);
		$path .= '&sensor=false';
		
		if ($languageCode != '')
		{
			$path .= '&language=' . $languageCode;
		}
		if ($locationBias != '')
		{
			$path .= '&region=' . $locationBias;
		}
		
		// Free
		$authenticationType = 'none';
		
		if (self::$authenticationType == 'business')
		{
			$authenticationType = 'client_IDAndSignature';
		}
		
		$path = XXX_GoogleMapsAPIHelpers::addAuthenticationToPath($path, $authenticationType, self::$serverKey, self::$client_ID, self::$cryptoKey);
		
		$uri = $protocol . $domain . $path;
		
		$response = XXX_GoogleMapsAPIHelpers::doGETRequest($uri);
		
		
			//XXX_Type::peakAtVariable($uri);
			//XXX_Type::peakAtVariable($response);
		
		if ($response != false && $response['status'] == 'OK')
		{
			$extraInformation = array
			(
				'lookupType' => 'rawAddressString',
				'rawAddressString' => $rawAddressString,
				'languageCode' => $languageCode,
				'locationBias' => $locationBias
			);
			
			$result = self::parseGeocoderResponse($response, $extraInformation);
		}
		else
		{
			self::$error = self::determineError($response['status']);
		}
		
		return $result;
	}
	
	public static function lookupGeoPosition ($latitude = 0, $longitude = 0, $languageCode = 'en', $locationBias = '')
	{
		$result = false;
		
		self::$error = false;
		
		// http://maps.googleapis.com/maps/api/geocode/json?address=1600+Amphitheatre+Parkway,+Mountain+View,+CA&sensor=true_or_false
		
		if (self::$httpsOnly)
		{
			$protocol = 'https://';
		}
		else
		{	
			$protocol = 'http://';
			
			if (class_exists('XXX_HTTPServer') && XXX_HTTPServer::$encryptedConnection)
			{
				$protocol = 'https://';
			}
		}
		
		$domain = 'maps.googleapis.com';
		$path = '/maps/api/geocode/json';
		$path .= '?';
		$path .= 'latlng=' . urlencode($latitude . ',' . $longitude);
		$path .= '&sensor=false';
		
		if ($languageCode != '')
		{
			$path .= '&language=' . $languageCode;
		}
		if ($locationBias != '')
		{
			$path .= '&region=' . $locationBias;
		}
		
		
		// Free
		$authenticationType = 'none';
		
		if (self::$authenticationType == 'business')
		{
			$authenticationType = 'client_IDAndSignature';
		}
		
		$path = XXX_GoogleMapsAPIHelpers::addAuthenticationToPath($path, $authenticationType, self::$serverKey, self::$client_ID, self::$cryptoKey);
		
		$uri = $protocol . $domain . $path;
		
		$response = XXX_GoogleMapsAPIHelpers::doGETRequest($uri);
		
		//XXX_Type::peakAtVariable($response);
		
		if ($response != false && $response['status'] == 'OK')
		{
			$extraInformation = array
			(
				'lookupType' => 'geoPosition',
				'latitude' => $latitude,
				'longitude' => $longitude,
				'languageCode' => $languageCode,
				'locationBias' => $locationBias
			);
			
			$result = self::parseGeocoderResponse($response, $extraInformation);
		}
		else
		{
			self::$error = self::determineError($response['status']);
		}
		
		return $result;
	}
		
	public static function determineError ($status = '')
	{
		$result = false;
		
		switch ($status)
		{
			case 'INVALID_REQUEST':
				// generally indicates that the query (address or latlng) is missing.
				$result = 'invalidRequest';
				break;
			case 'OVER_QUERY_LIMIT':
				// indicates that you are over your quota.
				$result = 'overQueryLimit';
				break;
			case 'REQUEST_DENIED':
				// indicates that your request was denied, generally because of lack of a sensor parameter.
				$result = 'requestDenied';
				break;
			case 'UNKNOWN_ERROR':
				// indicates that the request could not be processed due to a server error. The request may succeed if you try again.
				$result = 'unknownError';
				break;
			case 'ZERO_RESULTS':
				// indicates that the geocode was successful but returned no results. This may occur if the geocode was passed a non-existent address or a latlng in a remote location.
				$result = 'noResults';
				break;
		}
		
		return $result;
	}
		
	public static function parseGeocoderResponse ($response = array(), $extraInformation = array())
	{
		$results = false;
		
		if (XXX_Array::getFirstLevelItemTotal($response['results']))
		{
			$results = array();
			
			foreach ($response['results'] as $result)
			{
				$results[] = self::parseGeocoderResult($result, $extraInformation);
			}
		}
		
		return $results;
	}	
	
	public static function parseGeocoderResult ($geocoderResult = array(), $extraInformation = array())
	{
		$result = false;
		
		if ($geocoderResult['formatted_address'] != '')
		{
			$result = array();
			
			if (XXX_Type::isArray($extraInformation))
			{
				$result = XXX_Array::merge($result, $extraInformation);
			}
			
			$result['latitude'] = $geocoderResult['geometry']['location']['lat'];
			$result['longitude'] = $geocoderResult['geometry']['location']['lng'];
			$result['formattedAddressString'] = $geocoderResult['formatted_address'];
			$result['types'] = $geocoderResult['types'];
			
			$result['precisionType'] = $geocoderResult['geometry']['location_type'];
			
			$result['isPartialMatch'] = XXX_Type::makeBoolean($geocoderResult['partial_match']);
			
			for ($i = 0, $iEnd = count($geocoderResult['address_components']); $i < $iEnd; ++$i)
			{
				$addressComponent = $geocoderResult['address_components'][$i];
				
				for ($j = 0, $jEnd = count($addressComponent['types']); $j < $jEnd; ++$j)
				{
					$type = $addressComponent['types'][$j];
					
					if ($type == 'street_number')
					{
						$result['streetNumber'] = $addressComponent['long_name'];
					}
					else if ($type == 'route')
					{
						$result['street'] = $addressComponent['long_name'];
					}
					else if ($type == 'locality')
					{
						$result['city'] = $addressComponent['long_name'];
					}
					else if ($type == 'administrative_area_level_1')
					{
						$result['stateOrProvince'] = $addressComponent['long_name'];
						$result['stateOrProvinceCode'] = $addressComponent['short_name'];
					}
					else if ($type == 'country')
					{
						$result['country'] = $addressComponent['long_name'];
						$result['countryCode'] = $addressComponent['short_name'];
					}
					else if ($type == 'postal_code')
					{
						$result['postalCode'] = $addressComponent['short_name'];
					}
				}
			}
			
			$result = $result;
		}
		
		return $result;
	}
}

?>