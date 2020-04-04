<?php

namespace Slate\Connectors\GSuite;

use Cache;
use Emergence\Logger;
use Emergence\Http\Message\Request;
use Emergence\Http\Message\Uri;
use Emergence\People\IPerson;
use Emergence\People\ContactPoint\Email;
use Firebase\JWT\JWT;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;

class API
{
    public static $clientId;
    public static $clientEmail;
    public static $privateKey;

    public static $domain;

    public static $skew = 60;
    public static $expiry = 3600;

    public static $defaultLogger = Logger::class;

    /*
    * Returns \Emergence\Http\Message\Uri Object
    */

    public static function buildUrl($url)
    {
        $apiHost = 'https://www.googleapis.com/';

        if ($url instanceof UriInterface) {
            if ($url->getScheme() && $url->getHost()) {
                return $url;
            }
        } else {
            if (strpos($url, 'https://') === 0 || strpos($url, 'http://') === 0) {
                return $url;
            }
        }

        return new Uri($apiHost . ltrim((string)$url, '/'));
    }

    public static function formatHeaders(array $headers = [])
    {
        $formattedHeaders = [];

        foreach ($headers as $name => $values) {
            if (is_array($values)) {
                $formattedHeaders[] = sprintf('%s: %s', $name, join(', ', $values));
            } else {
                $formattedHeaders[] = sprintf('%s: %s', $name, (string)$values);
            }
        }

        return $formattedHeaders;
    }

    public static function buildRequest($method, $path, array $params = null, array $headers = [], array $options = [])
    {
        // build method
        $method = strtolower($method);
        // build url
        $url = static::buildUrl($path);

        if ('get' == $method && !empty($params)) {
            $query = preg_replace('/(%5B)\d+(%5D=)/i', '$1$2', http_build_query($params));
            $currentQuery = $url->getQuery();
            $url = $url->withQuery(join('&', array_filter([$query, $currentQuery])));
        }

        // build body
        $body = null;

        if ('get' != $method && !empty($params)) {
            if (isset($options['json_encode'])) {
                $headers['Content-Type'] = 'application/json';
                $body = json_encode($params);
            } else {
                $headers['Content-Type'] = 'application/x-www-form-urlencoded';
                $body = http_build_query($params);
            }
        }

        // return PSR-7 request
        return new Request($method, $url, $headers, $body);
    }

    public static function buildAndExecuteRequest($method, $path, array $params = null, array $headers = [], array $options = [])
    {
        $request = static::buildRequest($method, $path, $params, $headers, $options);

        return static::execute($request, $options);
    }

    public static function execute(MessageInterface $Request, array $options = [], LoggerInterface $Logger = null)
    {
        if (!$Logger) {
            $Logger = new static::$defaultLogger();
        }

        $Request = $Request->withAddedHeader('User-Agent', 'emergence');
        // configure curl
        $ch = curl_init((string)$Request->getUri());

        // configure output
        if (!empty($options['outputPath'])) {
            $fp = fopen($options['outputPath'], 'w');
            curl_setopt($ch, CURLOPT_FILE, $fp);
        } else {
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        }

        // configure method and body
        if (strtolower($Request->getMethod()) == 'post') {
            curl_setopt($ch, CURLOPT_POST, true);

            if (!empty((string)$Request->getBody())) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, (string)$Request->getBody());
            }

        } else if (strtolower($Request->getMethod()) != 'get') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $Request->getMethod());
        }

        // configure headers
        curl_setopt($ch, CURLOPT_HTTPHEADER, static::formatHeaders($Request->getHeaders()));

        // execute request
        $result = curl_exec($ch);
        curl_close($ch);

        if (isset($fp)) {
            fclose($fp);
        } elseif (!isset($options['decodeJson']) || $options['decodeJson']) {
            $result = json_decode($result, true);
        }

        return $result;
    }

    public static function getDomainEmail(IPerson $User = null)
    {
        if (!$User) {
            $User = $GLOBALS['Session']->Person;
        }

        if (is_a($User->PrimaryEmail, Email::class) && $User->PrimaryEmail->getDomainName() == static::$domain) {
            return $User->PrimaryEmail;
        }

        $DomainEmailPoint = null;
        foreach ($User->ContactPoints AS $ContactPoint) {
            if (is_a($ContactPoint, Email::class) && $ContactPoint->getDomainName() == static::$domain) {
                $DomainEmailPoint = $ContactPoint;
                break;
            }
        }

        return $DomainEmailPoint;
    }

    protected static function getAuthorizationHeaders($scope, $user = null)
    {
        $accessToken = static::getAccessToken(
            $scope,
            $user
        );

        return [
            'Authorization' => 'Bearer '. $accessToken
        ];
    }

    public static function getAccessToken($scope, $user = null, $ignoreCache = false)
    {
        $cacheKey = sprintf('gsuite_accesstoken:%s/%s', $scope, $user ? $user : static::$clientEmail);

        if ($ignoreCache === true || !$token = Cache::fetch($cacheKey)) {

            $assertion = [
                'iss' => static::$clientEmail,
                'aud' => (string)static::buildUrl('/oauth2/v4/token'),
                'exp' => time() + static::$expiry,
                'iat' => time() - static::$skew,
                'scope' => $scope
            ];

            if (!empty($user)) {
                $assertion['sub'] = $user;
            }

            $params = [
                'assertion' => JWT::encode($assertion, static::$privateKey, 'RS256'),
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer'
            ];

            $response = static::buildAndExecuteRequest('POST', '/oauth2/v4/token', $params);
            $token = $response['access_token'];

            if (!$token) {
                throw new \Exception($response['error'] ?: 'Unable to get Access Token. Please contact an Administrator.');
            }

            Cache::store(
                $cacheKey,
                $token,
                $response['expires_in'] - static::$skew
            );
        }

        return $token;
    }

    public static function getAllResults($resultsKey, $path, array $params = [], array $headers = [], array $options = [])
    {
        if (!isset($params['maxResults'])) {
            $params['maxResults'] = 500;
        }

        if (isset($params['fields'])) {
            $params['fields'] .= ',nextPageToken';
        } else {
            $params['fields'] = 'nextPageToken';
        }

        $page = static::buildAndExecuteRequest('GET', $path, $params, $headers, $options);
        $results = $page[$resultsKey];

        while (!empty($page['nextPageToken'])) {
            $page = static::buildAndExecuteRequest('GET', $path, array_merge($params, [
                'pageToken' => $page['nextPageToken']
            ]), $headers, $options);

            $results = array_merge($results, $page[$resultsKey]);
        }

        return $results;
    }

    public static function getAllUsers($params = [])
    {
        $headers = static::getAuthorizationHeaders('https://www.googleapis.com/auth/admin.directory.user', (string)static::getDomainEmail());

        $params['domain'] = static::$domain;
        $path = new Uri('https://www.googleapis.com/admin/directory/v1/users');

        return static::getAllResults(
            'users',
            $path,
            $params,
            $headers
        );
    }

    // Patch user: https://developers.google.com/admin-sdk/directory/v1/reference/users/patch
    public static function patchUser($userKey, $data)
    {
        $headers = static::getAuthorizationHeaders('https://www.googleapis.com/auth/admin.directory.user', (string)static::getDomainEmail());
        return static::buildAndExecuteRequest('PATCH', "/admin/directory/v1/users/$userKey", $data, $headers);
    }

    // Create user: https://developers.google.com/admin-sdk/directory/v1/reference/users/insert
    public static function createUser($data)
    {
        $headers = static::getAuthorizationHeaders('https://www.googleapis.com/auth/admin.directory.user', (string)static::getDomainEmail());
        return static::executeRequest('POST', "/admin/directory/v1/users", $data, $headers);
    }
}