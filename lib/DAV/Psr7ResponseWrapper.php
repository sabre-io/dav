<?php


namespace Sabre\DAV;


use Sabre\HTTP\ResponseInterface;
use function GuzzleHttp\Psr7\stream_for;

class Psr7ResponseWrapper implements ResponseInterface
{
    /**
     * @var \Psr\Http\Message\ResponseInterface
     */
    private $response;

    /**
     * @var \Closure
     */
    private $responseFactory;

    public function __construct(\Closure $responseFactory)
    {
        $this->responseFactory = $responseFactory;
        $this->reset();
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
        throw new \Exception('not used');
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
        throw new \Exception('not used');
    }

    /**
     * Returns the message body, as it's internal representation.
     *
     * This could be either a string, a stream or a callback writing the body to php://output
     *
     * @return resource|string|callable
     */
    private $getBodyCalled = false;

    function getBody()
    {
        if (!$this->getBodyCalled) {
            $this->getBodyCalled = true;
            return $this->response->getBody()->getContents();
        }
        throw new \Exception('getBody should only be used once.');
    }

    /**
     * Updates the body resource with a new stream.
     *
     * @param resource|string|callable $body
     * @return void
     */
    function setBody($body)
    {
        if (is_callable($body)) {
            throw new \Exception('not used');
        }
        $this->response = $this->response->withBody(stream_for($body));

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
        return $this->response->getHeaderLine($name);
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
        $this->response = $this->response->withHeader($name, $value);
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
        foreach($headers as $name => $value) {
            $this->response = $this->response->withHeader($name, $value);
        }
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
        $this->response = $this->response->withAddedHeader($name, $value);
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
        foreach($headers as $name => $value) {
            $this->response = $this->response->withAddedHeader($name, $value);
        }
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
        $this->response = $this->response->withProtocolVersion($version);
    }

    /**
     * Returns the HTTP version.
     */
    function getHttpVersion(): string
    {
        return $this->response->getProtocolVersion();
    }

    /**
     * Returns the current HTTP status code.
     */
    function getStatus(): int
    {
        return $this->response->getStatusCode();
    }

    /**
     * Returns the human-readable status string.
     *
     * In the case of a 200, this may for example be 'OK'.
     */
    function getStatusText(): string
    {
        return $this->response->getReasonPhrase();
    }

    /**
     * Sets the HTTP status code.
     *
     * This can be either the full HTTP status code with human readable string,
     * for example: "403 I can't let you do that, Dave".
     *
     * Or just the code, in which case the appropriate default message will be
     * added.
     *
     * @param string|int $status
     * @throws \InvalidArgumentException
     * @return void
     */
    function setStatus($status)
    {
        if (is_string($status)) {
            $reason = substr($status, 3);
            $status = substr($status, 0, 3);
            $this->response = $this->response->withStatus($status, $reason);
        } else {
            $this->response = $this->response->withStatus($status);
        }
    }

    public function getResponse(): \Psr\Http\Message\ResponseInterface
    {
        return $this->response;
    }


    public function reset()
    {
        $responseFactory = $this->responseFactory;
        /** @var \Psr\Http\Message\ResponseInterface $response */
        $response = $responseFactory()->withStatus(500);

        if (Server::$exposeVersion) {
            $response = $response->withAddedHeader('X-Sabre-Version', Version::VERSION);
        }
        $this->response = $response;

    }
}