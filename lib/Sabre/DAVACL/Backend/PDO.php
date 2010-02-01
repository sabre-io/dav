<?php

class Sabre_DAVACL_Backend_PDO extends Sabre_DAVACL_Backend_Abstract {

    protected $pdo; 
    protected $aclFieldMap = array(
        '{DAV:}read'             => 'read',
        '{DAV:}write-content'    => 'writecontent',
        '{DAV:}write-properties' => 'writeprops',
        '{DAV:}write-acl'        => 'writeacl',
        '{DAV:}bind'             => 'bind',
        '{DAV:}unbind'           => 'unbind',
        '{DAV:}unlock'           => 'unlock',

    );

    function __construct(PDO $pdo) {

        $this->pdo = $pdo;

    }

    function getPrivilegesForPrincipal($uri,$principal) {

        $stmt = $this->pdo->prepare('SELECT read, writecontent, writeprops, writeacl, bind, unbind, unlock FROM acl WHERE uri = ? AND principal = ? AND special = 0'); 
        $stmt->execute(array($uri,$principal));
        $result = $stmt->fetch();

        if (!$result) return array();

        $xmlList = array();

        foreach($this->aclFieldMap as $pXml=>$pDb) {

            if ($result[$pDb]) $xmlList[$pXml] = true;

        }
        
        $grant = array();
        foreach($this->getFlatPrivilegeList() as $privilegeName=>$privilegeInfo) {
            
            if (isset($xmlList[$privilegeName])) {
                $grant[] = $privilegeName;
                continue;
            }

            if (isset($privilegeInfo['abstract']) && $privilegeInfo['abstract'] && isset($xmlList[$privilegeInfo['concrete']])) {
                $grant[] = $privilegeName;
                continue;
            }
                    
        }


        return $grant;

    }

    function getACL($uri) {

        $stmt = $this->pdo->prepare('SELECT principal, special, read, writecontent, writeprops, writeacl, bind, unbind, unlock FROM acl WHERE uri = ?');
        $stmt->execute(array($uri));
        $result = $stmt->fetchAll();

        if (!$result) return array();

        $acl = array();

        foreach($result as $row) {
            $ace = array(
                'principal' => $row['principal'],
                'special'   => $row['special'],
                'uri'       => $uri,
                'grant'     => array(),
            );

            foreach($this->aclFieldMap as $pXml=>$pDb) {
                if ($row[$pDb])
                    $ace['grant'][] = $pXml;
            
            }

            $acl[] = $ace;

        } 

        return $acl;

    }

    function setACL($uri,array $acl) {

        foreach($acl as $ace) {

            // TODO: transaction
            $stmt = $this->pdo->prepare('DELETE FROM acl WHERE uri = ?');
            $stmt->execute(array($uri));

            $stmt = $this->pdo->prepare('INSERT INTO acl (uri, principal, special, read, writecontent, writeprops, writeacl, bind, unbind, unlock) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $values = array($uri,$ace['principal'],$ace['special']);
            foreach($this->aclFieldMap as $pXML=>$pDb) {

                $values[] = in_array($pXML,$ace['grant'])?1:0;

            }

            $stmt->execute($values);


        }

    }

}
