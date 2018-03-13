<?php


namespace Sabre\DAV;


use function GuzzleHttp\Psr7\stream_for;
use GuzzleHttp\Psr7\StreamWrapper;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\ServerRequestInterface;
use function Sabre\HTTP\decodePath;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\scalar;
use function Sabre\Uri\normalize;

class Psr7RequestWrapper implements RequestInterface
{
    public $request;
    private $baseUrl = '/';

    public function __construct(ServerRequestInterface $request)
    {
        $this->request = $request;
    }


    /**
     * Returns the body as a readable stream resource.
     *
     * Note that the stream may not be rewindable, and therefore may only be
     * read once.
     *
     * @return resource
     */
    function getBodyAsStream()
    {
        return StreamWrapper::getResource($this->request->getBody());
    }

    /**
     * Returns the body as a string.
     *
     * Note that because the underlying data may be based on a stream, this
     * method could only work correctly the first time.
     *
     * @return string
     */
    function getBodyAsString(): string
    {
        return $this->request->getBody()->getContents();
    }

    /**
     * Returns the message body, as it's internal representation.
     *
     * This could be either a string, a stream or a callback writing the body to php://output
     *
     * @return resource|string|callable
     */
    function getBody()
    {
        return StreamWrapper::getResource($this->request->getBody());
    }

    /**
     * Updates the body resource with a new stream.
     *
     * @param resource|string|callable $body
     * @return void
     */
    function setBody($body)
    {
        $this->request = $this->request->withBody(stream_for($body));
    }

    /**
     * Returns all the HTTP headers as an array.
     *
     * Every header is returned as an array, with one or more values.
     */
    function getHeaders(): array
    {
        throw new \Exception('not used');
    }

    /**
     * Will return true or false, depending on if a HTTP header exists.
     */
    function hasHeader(string $name): bool
    {
        throw new \Exception('not used');
    }

    /**
     * Returns a specific HTTP header, based on it's name.
     *
     * The name must be treated as case-insensitive.
     * If the header does not exist, this method must return null.
     *
     * If a header appeared more than once in a HTTP request, this method will
     * concatenate all the values with a comma.
     *
     * Note that this not make sense for all headers. Some, such as
     * `Set-Cookie` cannot be logically combined with a comma. In those cases
     * you *should* use getHeaderAsArray().
     *
     * @return string|null
     */
    function getHeader(string $name)
    {
        return count($this->request->getHeader($name)) === 0 ? null : $this->request->getHeaderLine($name);
    }

    /**
     * Returns a HTTP header as an array.
     *
     * For every time the HTTP header appeared in the request or response, an
     * item will appear in the array.
     *
     * If the header did not exists, this method will return an empty array.
     *
     * @return string[]
     */
    function getHeaderAsArray(string $name): array
    {
        throw new \Exception('not used');
    }

    /**
     * Updates a HTTP header.
     *
     * The case-sensitity of the name value must be retained as-is.
     *
     * If the header already existed, it will be overwritten.
     *
     * @param string|string[] $value
     * @return void
     */
    function setHeader(string $name, $value)
    {
        $this->request = $this->request->withHeader($name, $value);
    }

    /**
     * Sets a new set of HTTP headers.
     *
     * The headers array should contain headernames for keys, and their value
     * should be specified as either a string or an array.
     *
     * Any header that already existed will be overwritten.
     *
     * @return void
     */
    function setHeaders(array $headers)
    {
        throw new \Exception('not used');
    }

    /**
     * Adds a HTTP header.
     *
     * This method will not overwrite any existing HTTP header, but instead add
     * another value. Individual values can be retrieved with
     * getHeadersAsArray.
     *
     * @param scalar $value
     * @return void
     */
    function addHeader(string $name, $value)
    {
        throw new \Exception('not used');
    }

    /**
     * Adds a new set of HTTP headers.
     *
     * Any existing headers will not be overwritten.
     *
     * @return void
     */
    function addHeaders(array $headers)
    {
        throw new \Exception('not used');
    }

    /**
     * Removes a HTTP header.
     *
     * The specified header name must be treated as case-insenstive.
     * This method should return true if the header was successfully deleted,
     * and false if the header did not exist.
     */
    function removeHeader(string $name): bool
    {
        throw new \Exception('not used');
    }

    /**
     * Sets the HTTP version.
     *
     * Should be 1.0, 1.1 or 2.0.
     *
     * @return void
     */
    function setHttpVersion(string $version)
    {
        throw new \Exception('not used');
    }

    /**
     * Returns the HTTP version.
     */
    function getHttpVersion(): string
    {
        return $this->request->getProtocolVersion();
    }

    /**
     * Returns the current HTTP method
     */
    function getMethod(): string
    {
        return $this->request->getMethod();
    }

    /**
     * Sets the HTTP method
     *
     * @return void
     */
    function setMethod(string $method)
    {
        $this->request = $this->request->withMethod($method);
    }

    /**
     * Returns the request url.
     */
    function getUrl(): string
    {
        $uri = $this->request->getUri();
        $query = $uri->getQuery();
        if (empty($query)) {
            return $uri->getPath();
        }

        return $uri->getPath() . '?' . $query;
    }

    /**
     * Sets the request url.
     *
     * @return void
     */
    function setUrl(string $url)
    {
        $this->request = $this->request->withUri(new Uri($url));
    }

    /**
     * Returns the absolute url.
     */
    function getAbsoluteUrl(): string
    {
        $uri = $this->request->getUri();
        return "{$uri->getScheme()}://{$uri->getHost()}{$uri->getPath()}";
    }

    /**
     * Sets the absolute url.
     *
     * @return void
     */
    function setAbsoluteUrl(string $url)
    {
        throw new \Exception('not used');
    }

    /**
     * Returns the current base url.
     */
    function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Sets a base url.
     *
     * This url is used for relative path calculations.
     *
     * The base url should default to /
     *
     * @return void
     */
    function setBaseUrl(string $url)
    {
        $this->baseUrl = $url;
    }

    /**
     * Implementation borrowed from sabre/http
     */
    function getPath() : string {

        // Removing duplicated slashes.
        $uri = str_replace('//', '/', $this->getUrl());


        $uri = normalize($uri);
        $baseUri = normalize($this->getBaseUrl());

        if (strpos($uri, $baseUri) === 0) {

            // We're not interested in the query part (everything after the ?).
            list($uri) = explode('?', $uri);
            return trim(decodePath(substr($uri, strlen($baseUri))), '/');

        }
        // A special case, if the baseUri was accessed without a trailing
        // slash, we'll accept it as well.
        elseif ($uri . '/' === $baseUri) {

            return '';

        }

        throw new \LogicException('Requested uri (' . $this->getUrl() . ') is out of base uri (' . $this->getBaseUrl() . ')');
    }

    /**
     * Returns the list of query parameters.
     *
     * This is equivalent to PHP's $_GET superglobal.
     */
    function getQueryParameters(): array
    {
        return $this->request->getQueryParams();
    }

    /**
     * Returns the POST data.
     *
     * This is equivalent to PHP's $_POST superglobal.
     */
    function getPostData(): array
    {
        return $this->request->getParsedBody();
    }

    /**
     * Sets the post data.
     *
     * This is equivalent to PHP's $_POST superglobal.
     *
     * This would not have been needed, if POST data was accessible as
     * php://input, but unfortunately we need to special case it.
     *
     * @return void
     */
    function setPostData(array $postData)
    {
        throw new \Exception('not used');
    }

    /**
     * Returns an item from the _SERVER array.
     *
     * If the value does not exist in the array, null is returned.
     *
     * @return string|null
     */
    function getRawServerValue(string $valueName)
    {
        return $this->request->getServerParams()[$valueName] ?? null;
    }

    /**
     * Sets the _SERVER array.
     *
     * @return void
     */
    function setRawServerData(array $data)
    {
        throw new \Exception('not used');
    }
}