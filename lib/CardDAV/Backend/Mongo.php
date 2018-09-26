<?php

declare(strict_types=1);

namespace Sabre\CardDAV\Backend;

use Sabre\CardDAV;
use Sabre\DAV;

/**
 * MongoDB CardDAV backend.
 *
 * This CardDAV backend uses mongoDB to store addressbooks
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Linagora Folks (lgs-openpaas-dev@linagora.com)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Mongo extends AbstractBackend implements SyncSupport
{
    /**
     * MongoDB connection.
     *
     * @var MongoDB
     */
    protected $db;

    /**
     * The collection name used to store addressbooks.
     */
    public $addressBooksCollectionName = 'addressbooks';

    /**
     * The collection name used to store cards.
     */
    public $cardsCollectionName = 'cards';

    /**
     * The collection name that will be used for tracking changes in address books.
     *
     * @var string
     */
    public $addressBookChangesCollectionName = 'addressbookchanges';

    /**
     * Sets up the object.
     *
     * @param \MongoDB\Database $db
     */
    public function __construct(\MongoDB\Database $db)
    {
        $this->db = $db;
    }

    /**
     * Returns the list of addressbooks for a specific user.
     *
     * @param string $principalUri
     *
     * @return array
     */
    public function getAddressBooksForUser($principalUri)
    {
        $fields = ['_id' => 1, 'uri' => 1, 'displayname' => 1, 'principaluri' => 1, 'privilege' => 1, 'type' => 1, 'description' => 1, 'synctoken' => 1];
        $query = ['principaluri' => $principalUri];

        $collection = $this->db->selectCollection($this->addressBooksCollectionName);
        $addressBooksCursor = $collection->find($query, ['projection' => $fields]);

        $addressBooks = [];

        foreach ($addressBooksCursor as $row) {
            $addressBooks[] = [
                'id' => $row['_id'],
                'uri' => $row['uri'],
                'principaluri' => $row['principaluri'],
                '{DAV:}displayname' => $row['displayname'],
                '{'.CardDAV\Plugin::NS_CARDDAV.'}addressbook-description' => $row['description'],
                '{http://calendarserver.org/ns/}getctag' => $row['synctoken'],
                '{http://sabredav.org/ns}sync-token' => $row['synctoken'] ? $row['synctoken'] : '0',
            ];
        }

        return $addressBooks;
    }

    /**
     * Updates properties for an address book.
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
     * @param string               $addressBookId
     * @param \Sabre\DAV\PropPatch $propPatch
     */
    public function updateAddressBook($addressBookId, \Sabre\DAV\PropPatch $propPatch)
    {
        $supportedProperties = [
            '{DAV:}displayname',
            '{'.CardDAV\Plugin::NS_CARDDAV.'}addressbook-description',
        ];

        $propPatch->handle($supportedProperties, function ($mutations) use ($addressBookId) {
            $updates = [];
            foreach ($mutations as $property => $newValue) {
                switch ($property) {
                    case '{DAV:}displayname':
                        $updates['displayname'] = $newValue;
                        break;
                    case '{'.CardDAV\Plugin::NS_CARDDAV.'}addressbook-description':
                        $updates['description'] = $newValue;
                        break;
                }
            }

            $collection = $this->db->selectCollection($this->addressBooksCollectionName);
            $updatedAddressBook = $collection->findOneAndUpdate(
                [
                    '_id' => $this->getObjectId($addressBookId),
                ],
                [
                    '$set' => $updates,
                ],
                [
                    'projection' => ['principaluri' => 1, 'uri' => 1],
                    'returnDocument' => \MongoDB\Operation\FindOneAndUpdate::RETURN_DOCUMENT_AFTER,
                ]
            );

            $this->addChange($addressBookId, '', 2);

            return true;
        });
    }

    /**
     * Creates a new address book.
     *
     * @param string $principalUri
     * @param string $url          just the 'basename' of the url
     * @param array  $properties
     *
     * @return int Last insert id
     */
    public function createAddressBook($principalUri, $url, array $properties)
    {
        $values = [
            'displayname' => null,
            'description' => null,
            'principaluri' => $principalUri,
            'uri' => $url,
            'synctoken' => 1,
        ];

        foreach ($properties as $property => $newValue) {
            switch ($property) {
                case '{DAV:}displayname':
                    $values['displayname'] = $newValue;
                    break;
                case '{'.CardDAV\Plugin::NS_CARDDAV.'}addressbook-description':
                    $values['description'] = $newValue;
                    break;
                default:
                    throw new DAV\Exception\BadRequest('Unknown property: '.$property);
            }
        }

        $collection = $this->db->selectCollection($this->addressBooksCollectionName);
        $modified = $collection->findOneAndUpdate(
            [
                'principaluri' => $principalUri,
                'uri' => $url,
            ],
            [
                '$set' => $values,
            ],
            [
                'projection' => ['_id' => 1],
                'upsert' => true,
                'returnDocument' => \MongoDB\Operation\FindOneAndUpdate::RETURN_DOCUMENT_AFTER,
            ]
        );

        return (string) $modified['_id'];
    }

    /**
     * Deletes an entire addressbook and all its contents.
     *
     * @param mixed $addressBookId
     */
    public function deleteAddressBook($addressBookId)
    {
        $mongoId = $this->getObjectId($addressBookId);

        $collection = $this->db->selectCollection($this->addressBooksCollectionName);
        $collection->deleteMany(['_id' => $mongoId]);

        $collection = $this->db->selectCollection($this->cardsCollectionName);
        $collection->deleteMany(['addressbookid' => $mongoId]);

        $collection = $this->db->selectCollection($this->addressBookChangesCollectionName);
        $collection->deleteMany(['_id' => $mongoId]);
    }

    /**
     * Returns all cards for a specific addressbook id.
     *
     * This method should return the following properties for each card:
     *   * carddata - raw vcard data
     *   * uri - Some unique url
     *   * lastmodified - A unix timestamp
     *
     * It's recommended to also return the following properties:
     *   * etag - A unique etag. This must change every time the card changes.
     *   * size - The size of the card in bytes.
     *
     * If these last two properties are provided, less time will be spent
     * calculating them. If they are specified, you can also ommit carddata.
     * This may speed up certain requests, especially with large cards.
     *
     * @param mixed $addressbookId
     *
     * @return array
     */
    public function getCards($addressbookId)
    {
        $fields = ['_id' => 1, 'uri' => 1, 'lastmodified' => 1, 'etag' => 1, 'size' => 1];
        $query = ['addressbookid' => $this->getObjectId($addressbookId)];
        $collection = $this->db->selectCollection($this->cardsCollectionName);

        $cardscursor = $collection->find($query, ['projection' => $fields]);

        $result = [];
        foreach ($cardscursor as $card) {
            $card = $card->getArrayCopy();

            $card['id'] = (string) $card['_id'];
            unset($card['_id']);

            $result[] = $card;
        }

        return $result;
    }

    /**
     * Returns a specific card.
     *
     * The same set of properties must be returned as with getCards. The only
     * exception is that 'carddata' is absolutely required.
     *
     * If the card does not exist, you must return false.
     *
     * @param mixed  $addressBookId
     * @param string $cardUri
     *
     * @return array
     */
    public function getCard($addressBookId, $cardUri)
    {
        $fields = ['_id' => 1, 'uri' => 1, 'lastmodified' => 1, 'carddata' => 1, 'etag' => 1, 'size' => 1];
        $query = ['addressbookid' => $this->getObjectId($addressBookId), 'uri' => $cardUri];
        $collection = $this->db->selectCollection($this->cardsCollectionName);

        $card = $collection->findOne($query, ['projection' => $fields]);

        if ($card) {
            $card = $card->getArrayCopy();

            $card['id'] = (string) $card['_id'];
            unset($card['_id']);

            return $card;
        } else {
            return false;
        }
    }

    /**
     * Returns a list of cards.
     *
     * This method should work identical to getCard, but instead return all the
     * cards in the list as an array.
     *
     * If the backend supports this, it may allow for some speed-ups.
     *
     * @param mixed $addressBookId
     * @param array $uris
     *
     * @return array
     */
    public function getMultipleCards($addressBookId, array $uris)
    {
        $fields = ['_id' => 1, 'uri' => 1, 'lastmodified' => 1, 'carddata' => 1, 'etag' => 1, 'size' => 1];
        $query = [
            'addressbookid' => $this->getObjectId($addressBookId),
            'uri' => ['$in' => $uris],
        ];

        $collection = $this->db->selectCollection($this->cardsCollectionName);
        $cardscursor = $collection->find($query, ['projection' => $fields]);

        $cards = [];
        foreach ($cardscursor as $card) {
            $card = $card->getArrayCopy();

            $card['id'] = (string) $card['_id'];
            unset($card['_id']);
            $cards[] = $card;
        }

        return $cards;
    }

    /**
     * Creates a new card.
     *
     * The addressbook id will be passed as the first argument. This is the
     * same id as it is returned from the getAddressBooksForUser method.
     *
     * The cardUri is a base uri, and doesn't include the full path. The
     * cardData argument is the vcard body, and is passed as a string.
     *
     * It is possible to return an ETag from this method. This ETag is for the
     * newly created resource, and must be enclosed with double quotes (that
     * is, the string itself must contain the double quotes).
     *
     * You should only return the ETag if you store the carddata as-is. If a
     * subsequent GET request on the same card does not have the same body,
     * byte-by-byte and you did return an ETag here, clients tend to get
     * confused.
     *
     * If you don't return an ETag, you can just return null.
     *
     * @param mixed  $addressBookId
     * @param string $cardUri
     * @param string $cardData
     *
     * @return string|null
     */
    public function createCard($addressBookId, $cardUri, $cardData)
    {
        $collection = $this->db->selectCollection($this->cardsCollectionName);
        $obj = [
            'carddata' => $cardData,
            'uri' => $cardUri,
            'lastmodified' => time(),
            'addressbookid' => $this->getObjectId($addressBookId),
            'size' => strlen($cardData),
            'etag' => md5($cardData),
        ];
        $collection->insertOne($obj);

        $this->addChange($addressBookId, $cardUri, 1);

        return '"'.$obj['etag'].'"';
    }

    /**
     * Updates a card.
     *
     * The addressbook id will be passed as the first argument. This is the
     * same id as it is returned from the getAddressBooksForUser method.
     *
     * The cardUri is a base uri, and doesn't include the full path. The
     * cardData argument is the vcard body, and is passed as a string.
     *
     * It is possible to return an ETag from this method. This ETag should
     * match that of the updated resource, and must be enclosed with double
     * quotes (that is: the string itself must contain the actual quotes).
     *
     * You should only return the ETag if you store the carddata as-is. If a
     * subsequent GET request on the same card does not have the same body,
     * byte-by-byte and you did return an ETag here, clients tend to get
     * confused.
     *
     * If you don't return an ETag, you can just return null.
     *
     * @param mixed  $addressBookId
     * @param string $cardUri
     * @param string $cardData
     *
     * @return string|null
     */
    public function updateCard($addressBookId, $cardUri, $cardData)
    {
        $collection = $this->db->selectCollection($this->cardsCollectionName);
        $data = [
            'carddata' => $cardData,
            'lastmodified' => time(),
            'size' => strlen($cardData),
            'etag' => md5($cardData),
        ];
        $query = ['addressbookid' => $this->getObjectId($addressBookId), 'uri' => $cardUri];
        $collection->updateOne($query, ['$set' => $data]);

        $this->addChange($addressBookId, $cardUri, 2);

        return '"'.$data['etag'].'"';
    }

    /**
     * Deletes a card.
     *
     * @param mixed  $addressBookId
     * @param string $cardUri
     *
     * @return bool
     */
    public function deleteCard($addressBookId, $cardUri)
    {
        $query = ['addressbookid' => $this->getObjectId($addressBookId), 'uri' => $cardUri];
        $collection = $this->db->selectCollection($this->cardsCollectionName);
        $res = $collection->deleteOne($query, ['writeConcern' => new \MongoDB\Driver\WriteConcern(1)]);

        $this->addChange($addressBookId, $cardUri, 3);

        return 1 === $res->getDeletedCount();
    }

    /**
     * The getChanges method returns all the changes that have happened, since
     * the specified syncToken in the specified address book.
     *
     * This function should return an array, such as the following:
     *
     * [
     *   'syncToken' => 'The current synctoken',
     *   'added'   => [
     *      'new.txt',
     *   ],
     *   'modified'   => [
     *      'updated.txt',
     *   ],
     *   'deleted' => [
     *      'foo.php.bak',
     *      'old.txt'
     *   ]
     * ];
     *
     * The returned syncToken property should reflect the *current* syncToken
     * of the addressbook, as reported in the {http://sabredav.org/ns}sync-token
     * property. This is needed here too, to ensure the operation is atomic.
     *
     * If the $syncToken argument is specified as null, this is an initial
     * sync, and all members should be reported.
     *
     * The modified property is an array of nodenames that have changed since
     * the last token.
     *
     * The deleted property is an array with nodenames, that have been deleted
     * from collection.
     *
     * The $syncLevel argument is basically the 'depth' of the report. If it's
     * 1, you only have to report changes that happened only directly in
     * immediate descendants. If it's 2, it should also include changes from
     * the nodes below the child collections. (grandchildren)
     *
     * The $limit argument allows a client to specify how many results should
     * be returned at most. If the limit is not specified, it should be treated
     * as infinite.
     *
     * If the limit (infinite or not) is higher than you're willing to return,
     * you should throw a Sabre\DAV\Exception\TooMuchMatches() exception.
     *
     * If the syncToken is expired (due to data cleanup) or unknown, you must
     * return null.
     *
     * The limit is 'suggestive'. You are free to ignore it.
     *
     * @param mixed  $addressBookId
     * @param string $syncToken
     * @param int    $syncLevel
     * @param int    $limit
     *
     * @return array
     */
    public function getChangesForAddressBook($addressBookId, $syncToken, $syncLevel, $limit = null)
    {
        // Current synctoken
        $collection = $this->db->selectCollection($this->addressBooksCollectionName);
        $res = $collection->findOne(['_id' => $this->getObjectId($addressBookId)], ['projection' => ['synctoken' => 1]]);

        if (!$res || is_null($res['synctoken'])) {
            return null;
        }
        $currentToken = $res['synctoken'];

        $result = [
            'syncToken' => $currentToken,
            'added' => [],
            'modified' => [],
            'deleted' => [],
        ];

        if ($syncToken) {
            $collection = $this->db->selectCollection($this->addressBookChangesCollectionName);
            $query = [
                'addressbookid' => $this->getObjectId($addressBookId),
                'synctoken' => ['$gt' => $syncToken, '$lt' => $currentToken],
            ];

            $projection = [
                'uri' => 1,
                'operation' => 1,
            ];

            $options = [];

            if ($limit > 0) {
                $options['limit'] = $limit;
            }

            $res = $collection->find($query, $options);

            // This loop ensures that any duplicates are overwritten, only the
            // last change on a node is relevant.
            $changes = [];
            foreach ($res as $row) {
                $changes[$row['uri']] = $row['operation'];
            }

            foreach ($changes as $uri => $operation) {
                switch ($operation) {
                    case 1:
                        $result['added'][] = $uri;
                        break;
                    case 2:
                        $result['modified'][] = $uri;
                        break;
                    case 3:
                        $result['deleted'][] = $uri;
                        break;
                }
            }
        } else {
            // No synctoken supplied, this is the initial sync.
            $collection = $this->db->selectCollection($this->cardsCollectionName);
            $res = $collection->find(['addressbookid' => $this->getObjectId($addressBookId)], ['projection' => ['uri' => 1]]);

            $added = [];
            foreach ($res as $row) {
                $added[] = $row['uri'];
            }

            $result['added'] = $added;
        }

        return $result;
    }

    /**
     * Adds a change record to the addressbookchanges table.
     *
     * @param mixed  $addressBookId
     * @param string $objectUri
     * @param int    $operation     1 = add, 2 = modify, 3 = delete
     */
    protected function addChange($addressBookId, $objectUri, $operation)
    {
        $adrcollection = $this->db->selectCollection($this->addressBooksCollectionName);
        $fields = ['synctoken' => 1];
        $query = ['_id' => $this->getObjectId($addressBookId)];
        $res = $adrcollection->findOne($query, ['projection' => $fields]);

        $changecollection = $this->db->selectCollection($this->addressBookChangesCollectionName);
        $obj = [
            'uri' => $objectUri,
            'synctoken' => $res['synctoken'],
            'addressbookid' => $this->getObjectId($addressBookId),
            'operation' => $operation,
        ];
        $changecollection->insertOne($obj);

        $update = ['$inc' => ['synctoken' => 1]];
        $adrcollection->updateOne($query, $update);
    }

    private function getObjectId($id)
    {
        return $id instanceof \MongoDB\BSON\ObjectId ? $id : new \MongoDB\BSON\ObjectId($id);
    }
}
