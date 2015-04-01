<?php

namespace Sabre\DAVACL\Xml\Property;

use Sabre\DAV;
use Sabre\HTTP;

class ACLTest extends \PHPUnit_Framework_TestCase {

    function testConstruct() {

        $acl = new Acl(array());
        $this->assertInstanceOf('Sabre\DAVACL\Xml\Property\ACL', $acl);

    }

    function testSerializeEmpty() {

        $acl = new Acl(array());
        $xml = (new DAV\Server())->xml->write('{DAV:}root', $acl);

        $expected = '<?xml version="1.0"?>
<d:root xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" />';

        $this->assertXmlStringEqualsXmlString($expected, $xml);

    }

    function testSerialize() {

        $privileges = [  
            [ 
                'principal' => 'principals/evert',
                'privilege' => '{DAV:}write',
                'uri'       => 'articles',
            ],
            [ 
                'principal' => 'principals/foo',
                'privilege' => '{DAV:}read',
                'uri'       => 'articles',
                'protected' => true,
            ],
        ];

        $acl = new Acl($privileges);
        $xml = (new DAV\Server())->xml->write('{DAV:}root', $acl, '/');

        $expected = '<?xml version="1.0"?>
<d:root xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns">
  <d:ace>
    <d:principal>
      <d:href>/principals/evert/</d:href>
    </d:principal>
    <d:grant>
      <d:privilege>
        <d:write/>
      </d:privilege>
    </d:grant>
  </d:ace>
  <d:ace>
    <d:principal>
      <d:href>/principals/foo/</d:href>
    </d:principal>
    <d:grant>
      <d:privilege>
        <d:read/>
      </d:privilege>
    </d:grant>
    <d:protected/>
  </d:ace>
</d:root>
';
        $this->assertXmlStringEqualsXmlString($expected, $xml);

    }

    function testSerializeSpecialPrincipals() {

        $privileges = array(
            array(
                'principal' => '{DAV:}authenticated',
                'privilege' => '{DAV:}write',
                'uri'       => 'articles',
            ),
            array(
                'principal' => '{DAV:}unauthenticated',
                'privilege' => '{DAV:}write',
                'uri'       => 'articles',
            ),
            array(
                'principal' => '{DAV:}all',
                'privilege' => '{DAV:}write',
                'uri'       => 'articles',
            ),

        );

        $acl = new Acl($privileges);
        $xml = (new DAV\Server())->xml->write('{DAV:}root', $acl, '/');

        $expected = '<?xml version="1.0"?>
<d:root xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns">
  <d:ace>
    <d:principal>
      <d:authenticated/>
    </d:principal>
    <d:grant>
      <d:privilege>
        <d:write/>
      </d:privilege>
    </d:grant>
  </d:ace>
  <d:ace>
    <d:principal>
      <d:unauthenticated/>
    </d:principal>
    <d:grant>
      <d:privilege>
        <d:write/>
      </d:privilege>
    </d:grant>
  </d:ace>
  <d:ace>
    <d:principal>
      <d:all/>
    </d:principal>
    <d:grant>
      <d:privilege>
        <d:write/>
      </d:privilege>
    </d:grant>
  </d:ace>
</d:root>
';
        $this->assertXmlStringEqualsXmlString($expected, $xml);

    }

    function testUnserialize() {

        $source = '<?xml version="1.0"?>
<d:root xmlns:d="DAV:">
  <d:ace>
    <d:principal>
      <d:href>/principals/evert/</d:href>
    </d:principal>
    <d:grant>
      <d:privilege>
        <d:write/>
      </d:privilege>
    </d:grant>
  </d:ace>
  <d:ace>
    <d:principal>
      <d:href>/principals/foo/</d:href>
    </d:principal>
    <d:grant>
      <d:privilege>
        <d:read/>
      </d:privilege>
    </d:grant>
    <d:protected/>
  </d:ace>
</d:root>
';

        $reader = new \Sabre\Xml\Reader();
        $reader->elementMap['{DAV:}root'] = 'Sabre\DAVACL\Xml\Property\Acl';
        $reader->xml($source);

        $result = $reader->parse();
        $result = $result['value'];

        $this->assertInstanceOf('Sabre\\DAVACL\\Xml\\Property\\Acl', $result);

        $expected = array(
            array(
                'principal' => '/principals/evert/',
                'protected' => false,
                'privilege' => '{DAV:}write',
            ),
            array(
                'principal' => '/principals/foo/',
                'protected' => true,
                'privilege' => '{DAV:}read',
            ),
        );

        $this->assertEquals($expected, $result->getPrivileges());


    }

    /**
     * @expectedException Sabre\DAV\Exception\BadRequest
     */
    function testUnserializeNoPrincipal() {

        $source = '<?xml version="1.0"?>
<d:root xmlns:d="DAV:">
  <d:ace>
    <d:grant>
      <d:privilege>
        <d:write/>
      </d:privilege>
    </d:grant>
  </d:ace>
</d:root>
';


        $reader = new \Sabre\Xml\Reader();
        $reader->elementMap['{DAV:}root'] = 'Sabre\DAVACL\Xml\Property\Acl';
        $reader->xml($source);

        $result = $reader->parse();

    }

    function testUnserializeOtherPrincipal() {

        $source = '<?xml version="1.0"?>
<d:root xmlns:d="DAV:">
  <d:ace>
    <d:grant>
      <d:privilege>
        <d:write/>
      </d:privilege>
    </d:grant>
    <d:principal><d:authenticated /></d:principal>
  </d:ace>
  <d:ace>
    <d:grant>
      <d:privilege>
        <d:write/>
      </d:privilege>
    </d:grant>
    <d:principal><d:unauthenticated /></d:principal>
  </d:ace>
  <d:ace>
    <d:grant>
      <d:privilege>
        <d:write/>
      </d:privilege>
    </d:grant>
    <d:principal><d:all /></d:principal>
  </d:ace>
</d:root>
';

        $reader = new \Sabre\Xml\Reader();
        $reader->elementMap['{DAV:}root'] = 'Sabre\DAVACL\Xml\Property\Acl';
        $reader->xml($source);

        $result = $reader->parse();
        $result = $result['value'];

        $this->assertInstanceOf('Sabre\\DAVACL\\Xml\\Property\\Acl', $result);

        $expected = [ 
            [
                'principal' => '{DAV:}authenticated',
                'protected' => false,
                'privilege' => '{DAV:}write',
            ],
            [ 
                'principal' => '{DAV:}unauthenticated',
                'protected' => false,
                'privilege' => '{DAV:}write',
            ],
            [ 
                'principal' => '{DAV:}all',
                'protected' => false,
                'privilege' => '{DAV:}write',
            ],
        ];

        $this->assertEquals($expected, $result->getPrivileges());

    }

    /**
     * @expectedException Sabre\DAV\Exception\NotImplemented
     */
    function testUnserializeDeny() {

        $source = '<?xml version="1.0"?>
<d:root xmlns:d="DAV:">
  <d:ace>
    <d:deny>
      <d:privilege>
        <d:write/>
      </d:privilege>
    </d:deny>
    <d:principal><d:href>/principals/evert</d:href></d:principal>
  </d:ace>
</d:root>
';

        $reader = new \Sabre\Xml\Reader();
        $reader->elementMap['{DAV:}root'] = 'Sabre\DAVACL\Xml\Property\Acl';
        $reader->xml($source);

        $result = $reader->parse();

    }

}
