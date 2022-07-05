<?php

namespace Sabre\DAV\Auth\Backend;

/**
 * This is an authentication backend that uses LDAP.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Aisha Tammy <floss@bsd.ac>
 * @license http://sabre.io/license/ Modified BSD License
 */
class LDAP extends AbstractBasic
{
    /**
     * LDAP server uri, e.g. ldaps://ldap.example.org .
     *
     * @var string
     */
    protected $ldap_uri = 'ldap://127.0.0.1';

    /*
     * LDAP dn pattern for binding.
     *
     * @var string
     */
    protected $ldap_dn = 'mail=%u';

    /*
     * LDAP attribute to use for name.
     *
     * @var string
     */
    protected $ldap_cn = 'cn';

    /*
     * LDAP attribute used for mail.
     *
     * @var string
     */
    protected $ldap_mail = 'mail';

    /**
     * Creates the backend object.
     *
     * @param string $ldap_uri
     * @param string $ldap_dn
     * @param string $ldap_cn
     * @param string $ldap_mail
     */
    public function __construct($ldap_uri = 'ldap://127.0.0.1', $ldap_dn = 'mail=%u', $ldap_cn = 'cn', $ldap_mail = 'mail')
    {
        $this->ldap_uri = $ldap_uri;
        $this->ldap_dn = $ldap_dn;
        $this->ldap_cn = $ldap_cn;
        $this->ldap_mail = $ldap_mail;
    }

    /**
     * Connects to an LDAP server and tries to authenticate.
     *
     * @param string $username
     * @param string $password
     *
     * @return bool
     */
    protected function ldapOpen($username, $password)
    {
        $conn = ldap_connect($this->ldap_uri);
        if (!$conn) {
            return false;
        }
        if (!ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3)) {
            return false;
        }

        $success = false;

        $dn = \Sabre\DAV\StringUtil::parseCyrusSasl($username, $this->ldap_dn);

        try {
            $bind = ldap_bind($conn, $dn, $password);
            if ($bind) {
                $success = true;
            }
        } catch (\ErrorException $e) {
            error_log($e->getMessage());
            error_log(ldap_error($conn));
        }

        if ($success) {
            $search_results = ldap_read($conn, $dn, '(objectclass=*)', [$this->ldap_cn, $this->ldap_mail]);
            $entry = ldap_get_entries($conn, $search_results);
            $this->userData = [$username, 'Jane Doe', 'unset-email'];
            if (!empty($entry[0][$this->ldap_cn])) {
                $this->userData[1] = $entry[0][$this->ldap_cn][0];
            }
            if (!empty($entry[0][$this->ldap_mail])) {
                $this->userData[2] = $entry[0][$this->ldap_mail][0];
            }
        }

        ldap_close($conn);

        return $success;
    }

    /**
     * Validates a username and password by trying to authenticate against LDAP.
     *
     * @param string $username
     * @param string $password
     *
     * @return bool
     */
    protected function validateUserPass($username, $password)
    {
        return $this->ldapOpen($username, $password);
    }
}
