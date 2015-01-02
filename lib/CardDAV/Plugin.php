<?php

namespace Sabre\CardDAV;

use Sabre\DAV;
use Sabre\DAVACL;
use Sabre\VObject;

/**
 * CardDAV plugin
 *
 * The CardDAV plugin adds CardDAV functionality to the WebDAV server
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Plugin extends DAV\ServerPlugin {

    /**
     * Url to the addressbooks
     */
    const ADDRESSBOOK_ROOT = 'addressbooks';

    /**
     * xml namespace for CardDAV elements
     */
    const NS_CARDDAV = 'urn:ietf:params:xml:ns:carddav';

    /**
     * Add urls to this property to have them automatically exposed as
     * 'directories' to the user.
     *
     * @var array
     */
    public $directories = array();

    /**
     * Server class
     *
     * @var Sabre\DAV\Server
     */
    protected $server;

    /**
     * Initializes the plugin
     *
     * @param DAV\Server $server
     * @return void
     */
    public function initialize(DAV\Server $server) {

        /* Events */
        $server->on('propFind',            [$this, 'propFindEarly']);
        $server->on('propFind',            [$this, 'propFindLate'],150);
        $server->on('propPatch',           [$this, 'propPatch']);
        $server->on('report',              [$this, 'report']);
        $server->on('onHTMLActionsPanel',  [$this, 'htmlActionsPanel']);
        $server->on('onBrowserPostAction', [$this, 'browserPostAction']);
        $server->on('beforeWriteContent',  [$this, 'beforeWriteContent']);
        $server->on('beforeCreateFile',    [$this, 'beforeCreateFile']);

        /* Namespaces */
        $server->xmlNamespaces[self::NS_CARDDAV] = 'card';

        /* Mapping Interfaces to {DAV:}resourcetype values */
        $server->resourceTypeMapping['Sabre\\CardDAV\\IAddressBook'] = '{' . self::NS_CARDDAV . '}addressbook';
        $server->resourceTypeMapping['Sabre\\CardDAV\\IDirectory'] = '{' . self::NS_CARDDAV . '}directory';

        /* Adding properties that may never be changed */
        $server->protectedProperties[] = '{' . self::NS_CARDDAV . '}supported-address-data';
        $server->protectedProperties[] = '{' . self::NS_CARDDAV . '}max-resource-size';
        $server->protectedProperties[] = '{' . self::NS_CARDDAV . '}addressbook-home-set';
        $server->protectedProperties[] = '{' . self::NS_CARDDAV . '}supported-collation-set';

        $server->propertyMap['{http://calendarserver.org/ns/}me-card'] = 'Sabre\\DAV\\Property\\Href';

        $this->server = $server;

    }

    /**
     * Returns a list of supported features.
     *
     * This is used in the DAV: header in the OPTIONS and PROPFIND requests.
     *
     * @return array
     */
    public function getFeatures() {

        return ['addressbook'];

    }

    /**
     * Returns a list of reports this plugin supports.
     *
     * This will be used in the {DAV:}supported-report-set property.
     * Note that you still need to subscribe to the 'report' event to actually
     * implement them
     *
     * @param string $uri
     * @return array
     */
    public function getSupportedReportSet($uri) {

        $node = $this->server->tree->getNodeForPath($uri);
        if ($node instanceof IAddressBook || $node instanceof ICard) {
            return array(
                 '{' . self::NS_CARDDAV . '}addressbook-multiget',
                 '{' . self::NS_CARDDAV . '}addressbook-query',
            );
        }
        return array();

    }


    /**
     * Adds all CardDAV-specific properties
     *
     * @param DAV\PropFind $propFind
     * @param DAV\INode $node
     * @return void
     */
    public function propFindEarly(DAV\PropFind $propFind, DAV\INode $node) {

        if ($node instanceof DAVACL\IPrincipal) {

            $path = $propFind->getPath();

            $propFind->handle('{' . self::NS_CARDDAV . '}addressbook-home-set', function() use ($path) {
                return new DAV\Property\Href($this->getAddressBookHomeForPrincipal($path) . '/');
            });

            if ($this->directories) $propFind->handle('{' . self::NS_CARDDAV . '}directory-gateway', function() {
                return new DAV\Property\HrefList($this->directories);
            });

        }

        if ($node instanceof ICard) {

            // The address-data property is not supposed to be a 'real'
            // property, but in large chunks of the spec it does act as such.
            // Therefore we simply expose it as a property.
            $propFind->handle('{' . self::NS_CARDDAV . '}address-data', function() use ($node) {
                $val = $node->get();
                if (is_resource($val))
                    $val = stream_get_contents($val);

                return $val;

            });

        }

        if ($node instanceof UserAddressBooks) {

            $propFind->handle('{http://calendarserver.org/ns/}me-card', function() use ($node) {

                $props = $this->server->getProperties($node->getOwner(), ['{http://sabredav.org/ns}vcard-url']);
                if (isset($props['{http://sabredav.org/ns}vcard-url'])) {

                    return new DAV\Property\Href(
                        $props['{http://sabredav.org/ns}vcard-url']
                    );

                }

            });

        }

    }

    /**
     * This event is triggered when a PROPPATCH method is executed
     *
     * @param string $path
     * @param DAV\PropPatch $propPatch
     * @return bool
     */
    public function propPatch($path, DAV\PropPatch $propPatch) {

        $node = $this->server->tree->getNodeForPath($path);
        if (!$node instanceof UserAddressBooks) {
            return true;
        }

        $meCard = '{http://calendarserver.org/ns/}me-card';

        $propPatch->handle($meCard, function($value) use ($node) {

            if ($value instanceof DAV\Property\IHref) {
                $value = $value->getHref();
                $value = $this->server->calculateUri($value);
            } elseif (!is_null($value)) {
                return 400;
            }

            $innerResult = $this->server->updateProperties(
                $node->getOwner(),
                array(
                    '{http://sabredav.org/ns}vcard-url' => $value,
                )
            );

            return $innerResult['{http://sabredav.org/ns}vcard-url'];

        });

    }

    /**
     * This functions handles REPORT requests specific to CardDAV
     *
     * @param string $reportName
     * @param \DOMNode $dom
     * @return bool
     */
    public function report($reportName,$dom) {

        switch($reportName) {
            case '{'.self::NS_CARDDAV.'}addressbook-multiget' :
                $this->server->transactionType = 'report-addressbook-multiget';
                $this->addressbookMultiGetReport($dom);
                return false;
            case '{'.self::NS_CARDDAV.'}addressbook-query' :
                $this->server->transactionType = 'report-addressbook-query';
                $this->addressBookQueryReport($dom);
                return false;
            default :
                return;

        }


    }

    /**
     * Returns the addressbook home for a given principal
     *
     * @param string $principal
     * @return string
     */
    protected function getAddressbookHomeForPrincipal($principal) {

        list(, $principalId) = \Sabre\HTTP\URLUtil::splitPath($principal);
        return self::ADDRESSBOOK_ROOT . '/' . $principalId;

    }


    /**
     * This function handles the addressbook-multiget REPORT.
     *
     * This report is used by the client to fetch the content of a series
     * of urls. Effectively avoiding a lot of redundant requests.
     *
     * @param \DOMNode $dom
     * @return void
     */
    public function addressbookMultiGetReport($dom) {

        $properties = array_keys(DAV\XMLUtil::parseProperties($dom->firstChild));

        $hrefElems = $dom->getElementsByTagNameNS('urn:DAV','href');
        $propertyList = array();

        $uris = [];
        foreach($hrefElems as $elem) {

            $uris[] = $this->server->calculateUri($elem->nodeValue);

        }

        $propertyList = array_values(
            $this->server->getPropertiesForMultiplePaths($uris, $properties)
        );

        $prefer = $this->server->getHTTPPRefer();

        $this->server->httpResponse->setStatus(207);
        $this->server->httpResponse->setHeader('Content-Type','application/xml; charset=utf-8');
        $this->server->httpResponse->setHeader('Vary','Brief,Prefer');
        $this->server->httpResponse->setBody($this->server->generateMultiStatus($propertyList, $prefer['return-minimal']));

    }

    /**
     * This method is triggered before a file gets updated with new content.
     *
     * This plugin uses this method to ensure that Card nodes receive valid
     * vcard data.
     *
     * @param string $path
     * @param DAV\IFile $node
     * @param resource $data
     * @param bool $modified Should be set to true, if this event handler
     *                       changed &$data.
     * @return void
     */
    public function beforeWriteContent($path, DAV\IFile $node, &$data, &$modified) {

        if (!$node instanceof ICard)
            return;

        $this->validateVCard($data, $modified);

    }

    /**
     * This method is triggered before a new file is created.
     *
     * This plugin uses this method to ensure that Card nodes receive valid
     * vcard data.
     *
     * @param string $path
     * @param resource $data
     * @param DAV\ICollection $parentNode
     * @param bool $modified Should be set to true, if this event handler
     *                       changed &$data.
     * @return void
     */
    public function beforeCreateFile($path, &$data, DAV\ICollection $parentNode, &$modified) {

        if (!$parentNode instanceof IAddressBook)
            return;

        $this->validateVCard($data, $modified);

    }

    /**
     * Checks if the submitted iCalendar data is in fact, valid.
     *
     * An exception is thrown if it's not.
     *
     * @param resource|string $data
     * @param bool $modified Should be set to true, if this event handler
     *                       changed &$data.
     * @return void
     */
    protected function validateVCard(&$data, &$modified) {

        // If it's a stream, we convert it to a string first.
        if (is_resource($data)) {
            $data = stream_get_contents($data);
        }

        $before = md5($data);

        // Converting the data to unicode, if needed.
        $data = DAV\StringUtil::ensureUTF8($data);

        if (md5($data) !== $before) $modified = true;

        try {

            $vobj = VObject\Reader::read($data);

        } catch (VObject\ParseException $e) {

            throw new DAV\Exception\UnsupportedMediaType('This resource only supports valid vcard data. Parse error: ' . $e->getMessage());

        }

        if ($vobj->name !== 'VCARD') {
            throw new DAV\Exception\UnsupportedMediaType('This collection can only support vcard objects.');
        }

        if (!isset($vobj->UID)) {
            // No UID in vcards is invalid, but we'll just add it in anyway.
            $vobj->add('UID', DAV\UUIDUtil::getUUID());
            $data = $vobj->serialize();
            $modified = true;
        }

    }


    /**
     * This function handles the addressbook-query REPORT
     *
     * This report is used by the client to filter an addressbook based on a
     * complex query.
     *
     * @param \DOMNode $dom
     * @return void
     */
    protected function addressbookQueryReport($dom) {

        $query = new AddressBookQueryParser($dom);
        $query->parse();

        $depth = $this->server->getHTTPDepth(0);

        if ($depth==0) {
            $candidateNodes = array(
                $this->server->tree->getNodeForPath($this->server->getRequestUri())
            );
        } else {
            $candidateNodes = $this->server->tree->getChildren($this->server->getRequestUri());
        }

        $validNodes = array();
        foreach($candidateNodes as $node) {

            if (!$node instanceof ICard)
                continue;

            $blob = $node->get();
            if (is_resource($blob)) {
                $blob = stream_get_contents($blob);
            }

            if (!$this->validateFilters($blob, $query->filters, $query->test)) {
                continue;
            }

            $validNodes[] = $node;

            if ($query->limit && $query->limit <= count($validNodes)) {
                // We hit the maximum number of items, we can stop now.
                break;
            }

        }

        $result = array();
        foreach($validNodes as $validNode) {

            if ($depth==0) {
                $href = $this->server->getRequestUri();
            } else {
                $href = $this->server->getRequestUri() . '/' . $validNode->getName();
            }

            list($result[]) = $this->server->getPropertiesForPath($href, $query->requestedProperties, 0);

        }

        $prefer = $this->server->getHTTPPRefer();

        $this->server->httpResponse->setStatus(207);
        $this->server->httpResponse->setHeader('Content-Type','application/xml; charset=utf-8');
        $this->server->httpResponse->setHeader('Vary','Brief,Prefer');
        $this->server->httpResponse->setBody($this->server->generateMultiStatus($result, $prefer['return-minimal']));

    }

    /**
     * Validates if a vcard makes it throught a list of filters.
     *
     * @param string $vcardData
     * @param array $filters
     * @param string $test anyof or allof (which means OR or AND)
     * @return bool
     */
    public function validateFilters($vcardData, array $filters, $test) {

        $vcard = VObject\Reader::read($vcardData);

        if (!$filters) return true;

        foreach($filters as $filter) {

            $isDefined = isset($vcard->{$filter['name']});
            if ($filter['is-not-defined']) {
                if ($isDefined) {
                    $success = false;
                } else {
                    $success = true;
                }
            } elseif ((!$filter['param-filters'] && !$filter['text-matches']) || !$isDefined) {

                // We only need to check for existence
                $success = $isDefined;

            } else {

                $vProperties = $vcard->select($filter['name']);

                $results = array();
                if ($filter['param-filters']) {
                    $results[] = $this->validateParamFilters($vProperties, $filter['param-filters'], $filter['test']);
                }
                if ($filter['text-matches']) {
                    $texts = array();
                    foreach($vProperties as $vProperty)
                        $texts[] = $vProperty->getValue();

                    $results[] = $this->validateTextMatches($texts, $filter['text-matches'], $filter['test']);
                }

                if (count($results)===1) {
                    $success = $results[0];
                } else {
                    if ($filter['test'] === 'anyof') {
                        $success = $results[0] || $results[1];
                    } else {
                        $success = $results[0] && $results[1];
                    }
                }

            } // else

            // There are two conditions where we can already determine whether
            // or not this filter succeeds.
            if ($test==='anyof' && $success) {
                return true;
            }
            if ($test==='allof' && !$success) {
                return false;
            }

        } // foreach

        // If we got all the way here, it means we haven't been able to
        // determine early if the test failed or not.
        //
        // This implies for 'anyof' that the test failed, and for 'allof' that
        // we succeeded. Sounds weird, but makes sense.
        return $test==='allof';

    }

    /**
     * Validates if a param-filter can be applied to a specific property.
     *
     * @todo currently we're only validating the first parameter of the passed
     *       property. Any subsequence parameters with the same name are
     *       ignored.
     * @param array $vProperties
     * @param array $filters
     * @param string $test
     * @return bool
     */
    protected function validateParamFilters(array $vProperties, array $filters, $test) {

        foreach($filters as $filter) {

            $isDefined = false;
            foreach($vProperties as $vProperty) {
                $isDefined = isset($vProperty[$filter['name']]);
                if ($isDefined) break;
            }

            if ($filter['is-not-defined']) {
                if ($isDefined) {
                    $success = false;
                } else {
                    $success = true;
                }

            // If there's no text-match, we can just check for existence
            } elseif (!$filter['text-match'] || !$isDefined) {

                $success = $isDefined;

            } else {

                $success = false;
                foreach($vProperties as $vProperty) {
                    // If we got all the way here, we'll need to validate the
                    // text-match filter.
                    $success = DAV\StringUtil::textMatch($vProperty[$filter['name']]->getValue(), $filter['text-match']['value'], $filter['text-match']['collation'], $filter['text-match']['match-type']);
                    if ($success) break;
                }
                if ($filter['text-match']['negate-condition']) {
                    $success = !$success;
                }

            } // else

            // There are two conditions where we can already determine whether
            // or not this filter succeeds.
            if ($test==='anyof' && $success) {
                return true;
            }
            if ($test==='allof' && !$success) {
                return false;
            }

        }

        // If we got all the way here, it means we haven't been able to
        // determine early if the test failed or not.
        //
        // This implies for 'anyof' that the test failed, and for 'allof' that
        // we succeeded. Sounds weird, but makes sense.
        return $test==='allof';

    }

    /**
     * Validates if a text-filter can be applied to a specific property.
     *
     * @param array $texts
     * @param array $filters
     * @param string $test
     * @return bool
     */
    protected function validateTextMatches(array $texts, array $filters, $test) {

        foreach($filters as $filter) {

            $success = false;
            foreach($texts as $haystack) {
                $success = DAV\StringUtil::textMatch($haystack, $filter['value'], $filter['collation'], $filter['match-type']);

                // Breaking on the first match
                if ($success) break;
            }
            if ($filter['negate-condition']) {
                $success = !$success;
            }

            if ($success && $test==='anyof')
                return true;

            if (!$success && $test=='allof')
                return false;


        }

        // If we got all the way here, it means we haven't been able to
        // determine early if the test failed or not.
        //
        // This implies for 'anyof' that the test failed, and for 'allof' that
        // we succeeded. Sounds weird, but makes sense.
        return $test==='allof';

    }

    /**
     * This event is triggered when fetching properties.
     *
     * This event is scheduled late in the process, after most work for
     * propfind has been done.
     */
    public function propFindLate(DAV\PropFind $propFind, DAV\INode $node) {

        // If the request was made using the SOGO connector, we must rewrite
        // the content-type property. By default SabreDAV will send back
        // text/x-vcard; charset=utf-8, but for SOGO we must strip that last
        // part.
        if (strpos($this->server->httpRequest->getHeader('User-Agent'),'Thunderbird')===false) {
            return;
        }
        $contentType = $propFind->get('{DAV:}getcontenttype');
        if (!$contentType || strpos($contentType, 'text/x-vcard')!==0) {
           return;
        }
        $propFind->set('{DAV:}getcontenttype', 'text/x-vcard');

    }

    /**
     * This method is used to generate HTML output for the
     * Sabre\DAV\Browser\Plugin. This allows us to generate an interface users
     * can use to create new addressbooks.
     *
     * @param DAV\INode $node
     * @param string $output
     * @return bool
     */
    public function htmlActionsPanel(DAV\INode $node, &$output) {

        if (!$node instanceof UserAddressBooks)
            return;

        $output.= '<tr><td colspan="2"><form method="post" action="">
            <h3>Create new address book</h3>
            <input type="hidden" name="sabreAction" value="mkaddressbook" />
            <label>Name (uri):</label> <input type="text" name="name" /><br />
            <label>Display name:</label> <input type="text" name="{DAV:}displayname" /><br />
            <input type="submit" value="create" />
            </form>
            </td></tr>';

        return false;

    }

    /**
     * This method allows us to intercept the 'mkaddressbook' sabreAction. This
     * action enables the user to create new addressbooks from the browser plugin.
     *
     * @param string $uri
     * @param string $action
     * @param array $postVars
     * @return bool
     */
    public function browserPostAction($uri, $action, array $postVars) {

        if ($action!=='mkaddressbook')
            return;

        $resourceType = array('{DAV:}collection','{urn:ietf:params:xml:ns:carddav}addressbook');
        $properties = array();
        if (isset($postVars['{DAV:}displayname'])) {
            $properties['{DAV:}displayname'] = $postVars['{DAV:}displayname'];
        }
        $this->server->createCollection($uri . '/' . $postVars['name'],$resourceType,$properties);
        return false;

    }
}
