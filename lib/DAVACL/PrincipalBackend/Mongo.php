<?php

declare(strict_types=1);

namespace Sabre\DAVACL\PrincipalBackend;

use Sabre\DAV;
use Sabre\DAV\MkCol;
use Sabre\Uri;

/**
 * MongoDB principal backend.
 *
 *
 * This backend assumes all principals are in a single collection. The default collection
 * is 'principals/', but this can be overridden.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Linagora Folks (lgs-openpaas-dev@linagora.com)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Mongo extends AbstractBackend
{
    /**
     * Mongo collection name for 'principals'.
     *
     * @var string
     */
    public $collectionName = 'principals';

    /**
     * Mongo collection name for 'group members'.
     *
     * @var string
     */
    public $groupMembersCollectionName = 'groupmembers';

    /**
     * MongoDB.
     *
     * @var MongoDB
     */
    protected $mongoDB;

    /**
     * A list of additional fields to support.
     *
     * @var array
     */
    protected $fieldMap = [
        /*
         * This property can be used to display the users' real name.
         */
        '{DAV:}displayname' => [
            'dbField' => 'displayname',
        ],

        /*
         * This is the users' primary email-address.
         */
        '{http://sabredav.org/ns}email-address' => [
            'dbField' => 'email',
        ],
    ];

    /**
     * Sets up the backend.
     *
     * @param \MongoDB $mongoDB
     */
    public function __construct($mongoDB)
    {
        $this->db = $mongoDB;
    }

    /**
     * Returns a list of principals based on a prefix.
     *
     * This prefix will often contain something like 'principals'. You are only
     * expected to return principals that are in this base path.
     *
     * You are expected to return at least a 'uri' for every user, you can
     * return any additional properties if you wish so. Common properties are:
     *   {DAV:}displayname
     *   {http://sabredav.org/ns}email-address - This is a custom SabreDAV
     *     field that's actualy injected in a number of other properties. If
     *     you have an email address, use this property.
     *
     * @param string $prefixPath
     *
     * @return array
     */
    public function getPrincipalsByPrefix($prefixPath)
    {
        $fields = [
            'uri',
        ];

        foreach ($this->fieldMap as $key => $value) {
            $fields[] = $value['dbField'];
        }
        $fields = array_fill_keys($fields, 1);

        $collection = $this->db->selectCollection($this->collectionName);
        $result = $collection->find([], ['projection' => $fields]);

        $principals = [];

        foreach ($result as $key => $value) {
            // Checking if the principal is in the prefix
            list($rowPrefix) = Uri\split($value['uri']);
            if ($rowPrefix !== $prefixPath) {
                continue;
            }

            $principal = [
                'uri' => $value['uri'],
            ];
            foreach ($this->fieldMap as $key => $fieldValue) {
                if ($value[$fieldValue['dbField']]) {
                    $principal[$key] = $value[$fieldValue['dbField']];
                }
            }
            $principals[] = $principal;
        }

        return $principals;
    }

    /**
     * Returns a specific principal, specified by it's path.
     * The returned structure should be the exact same as from
     * getPrincipalsByPrefix.
     *
     * @param string $path
     *
     * @return array
     */
    public function getPrincipalByPath($path)
    {
        $fields = [
            '_id',
            'uri',
        ];

        foreach ($this->fieldMap as $key => $value) {
            $fields[] = $value['dbField'];
        }

        $fields = array_fill_keys($fields, 1);

        $collection = $this->db->selectCollection($this->collectionName);
        $result = $collection->findOne(['uri' => $path], ['projection' => $fields]);

        if (!$result) {
            return;
        }
        $principal = [
            '_id' => $result['_id'],
            'uri' => $result['uri'],
        ];
        foreach ($this->fieldMap as $key => $value) {
            if ($result[$value['dbField']]) {
                $principal[$key] = $result[$value['dbField']];
            }
        }

        return $principal;
    }

    /**
     * Updates one ore more webdav properties on a principal.
     *
     * The list of mutations is stored in a Sabre\DAV\PropPatch object.
     * To do the actual updates, you must tell this object which properties
     * you're going to process with the handle() method.
     *
     * Calling the handle method is like telling the PropPatch object "I
     * promise I can handle updating this property".
     *
     * Read the PropPatch documentation for more info and examples.
     *
     * @param string        $path
     * @param DAV\PropPatch $propPatch
     */
    public function updatePrincipal($path, DAV\PropPatch $propPatch)
    {
        $propPatch->handle(array_keys($this->fieldMap), function ($properties) use ($path) {
            $collection = $this->db->selectCollection($this->collectionName);
            $query = ['uri' => $path];

            $values = [];

            foreach ($properties as $key => $value) {
                $dbField = $this->fieldMap[$key]['dbField'];
                $values[$dbField] = $value;
            }

            $collection->updateOne($query, ['$set' => $values]);

            return true;
        });
    }

    /**
     * This method is used to search for principals matching a set of
     * properties.
     *
     * This search is specifically used by RFC3744's principal-property-search
     * REPORT.
     *
     * The actual search should be a unicode-non-case-sensitive search. The
     * keys in searchProperties are the WebDAV property names, while the values
     * are the property values to search on.
     *
     * By default, if multiple properties are submitted to this method, the
     * various properties should be combined with 'AND'. If $test is set to
     * 'anyof', it should be combined using 'OR'.
     *
     * This method should simply return an array with full principal uri's.
     *
     * If somebody attempted to search on a property the backend does not
     * support, you should simply return 0 results.
     *
     * You can also just return 0 results if you choose to not support
     * searching at all, but keep in mind that this may stop certain features
     * from working.
     *
     * @param string $prefixPath
     * @param array  $searchProperties
     * @param string $test
     *
     * @return array
     */
    public function searchPrincipals($prefixPath, array $searchProperties, $test = 'allof')
    {
        if (0 == count($searchProperties)) {
            return [];
        }    //No criteria
        $collection = $this->db->selectCollection($this->collectionName);

        $query = [];
        foreach ($searchProperties as $property => $value) {
            switch ($property) {
            case '{DAV:}displayname':
              $query[] = ['displayname' => ['$regex' => preg_quote($value), '$options' => 'i']];
              break;
            case '{http://sabredav.org/ns}email-address':
              $query[] = ['email' => ['$regex' => preg_quote($value), '$options' => 'i']];
              break;
            default:
              // Unsupported property
              return [];
          }
        }
        if (count($query) > 0 && 'allof' == $test) {
            $query = ['$and' => $query];
        } elseif (count($query) > 0 && 'anyof' == $test) {
            $query = ['$or' => $query];
        } else {
            return [];
        }

        $result = $collection->find($query, ['projection' => ['uri' => 1]]);

        $principals = [];
        foreach ($result as $row) {
            // Checking if the principal is in the prefix
            list($rowPrefix) = Uri\split($row['uri']);
            if ($rowPrefix !== $prefixPath) {
                continue;
            }

            $principals[] = $row['uri'];
        }

        return $principals;
    }

    /**
     * Finds a principal by its URI.
     *
     * This method may receive any type of uri, but mailto: addresses will be
     * the most common.
     *
     * Implementation of this API is optional. It is currently used by the
     * CalDAV system to find principals based on their email addresses. If this
     * API is not implemented, some features may not work correctly.
     *
     * This method must return a relative principal path, or null, if the
     * principal was not found or you refuse to find it.
     *
     * @param string $uri
     * @param string $principalPrefix
     *
     * @return string
     */
    public function findByUri($uri, $principalPrefix)
    {
        $value = null;
        $scheme = null;
        list($scheme, $value) = explode(':', $uri, 2);
        if (empty($value)) {
            return null;
        }

        $uri = null;
        switch ($scheme) {
            case 'mailto':
                $collection = $this->db->selectCollection($this->collectionName);
                $query = ['email' => $value];
                $result = $collection->find($query, ['projection' => ['uri' => 1]]);

                foreach ($result as $row) {
                    // Checking if the principal is in the prefix
                    list($rowPrefix) = Uri\split($row['uri']);
                    if ($rowPrefix !== $principalPrefix) {
                        continue;
                    }

                    $uri = $row['uri'];
                    break; //Stop on first match
                }
                break;
            default:
                //unsupported uri scheme
                return null;
        }

        return $uri;
    }

    /**
     * Returns the list of members for a group-principal.
     *
     * @param string $principal
     *
     * @return array
     */
    public function getGroupMemberSet($principal)
    {
        $principal = $this->getPrincipalByPath($principal);
        if (!$principal) {
            throw new DAV\Exception('Principal not found');
        }
        $collection = $this->db->selectCollection($this->collectionName);

        $query = [
            [
                '$lookup' => [
                    'from' => $this->groupMembersCollectionName,
                    'localField' => '_id',
                    'foreignField' => 'member_id',
                    'as' => 'groupMembers',
                ],
            ], [
                '$unwind' => '$groupMembers',
            ],
        ];

        $res = $collection->aggregate($query, ['projection' => ['uri' => 1]]);
        $result = [];

        foreach ($res as $row) {
            $result[] = $row['uri'];
        }

        return $result;
    }

    /**
     * Returns the list of groups a principal is a member of.
     *
     * @param string $principal
     *
     * @return array
     */
    public function getGroupMembership($principal)
    {
        $principal = $this->getPrincipalByPath($principal);
        if (!$principal) {
            throw new DAV\Exception('Principal not found');
        }
        $collection = $this->db->selectCollection($this->collectionName);
        $query = [
            [
                '$lookup' => [
                    'from' => $this->groupMembersCollectionName,
                    'localField' => '_id',
                    'foreignField' => 'principal_id',
                    'as' => 'groupMembers',
                ],
            ], [
                '$unwind' => '$groupMembers',
            ],
        ];

        $res = $collection->aggregate($query, ['projection' => ['uri' => 1]]);

        $result = [];
        foreach ($res as $row) {
            $result[] = $row['uri'];
        }

        return $result;
    }

    /**
     * Updates the list of group members for a group principal.
     *
     * The principals should be passed as a list of uri's.
     *
     * @param string $principal
     * @param array  $members
     */
    public function setGroupMemberSet($principal, array $members)
    {
        // Grabbing the list of principal id's.
        $principal = $this->getPrincipalByPath($principal);
        $principalId = $principal['_id'];
        $memberIds = [];

        if (!empty($members)) {
            $uris = [];
            foreach ($members as $member) {
                $uris[] = ['uri' => $member];
            }
            $query = [
                '$and' => $uris,
            ];
            $collection = $this->db->selectCollection($this->collectionName);
            $result = $collection->find($query, ['projection' => ['uri' => 1, '_id' => 1]]);

            foreach ($result as $row) {
                if ($row['uri'] !== $principal['uri']) {
                    $memberIds[] = $row['_id'];
                }
            }
        }

        if (!$principalId) {
            throw new DAV\Exception('Principal not found');
        }
        // Wiping out old members
        $query = [
            'principal_id' => $principalId,
        ];
        $collection = $this->db->selectCollection($this->groupMembersCollectionName);
        $res = $collection->deleteMany($query, ['writeConcern' => new \MongoDB\Driver\WriteConcern(1)]);

        foreach ($memberIds as $memberId) {
            $query = [
                'principal_id' => $principalId,
                'member_id' => $memberId,
            ];
            $collection->insertOne($query);
        }
    }

    /**
     * Creates a new principal.
     *
     * This method receives a full path for the new principal. The mkCol object
     * contains any additional webdav properties specified during the creation
     * of the principal.
     *
     * @param string $path
     * @param MkCol  $mkCol
     */
    public function createPrincipal($path, MkCol $mkCol)
    {
        $query = [
            'uri' => $path,
        ];
        $collection = $this->db->selectCollection($this->collectionName);
        $collection->insertOne($query);

        $this->updatePrincipal($path, $mkCol);
    }
}
