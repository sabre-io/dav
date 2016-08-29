<?php
namespace Sabre\DAV\Auth\Backend;

/**
 * LDAP authentification backend class
 *
 * This class can be used to authenticate for sabredav using LDAP.
 * This function is tested with OpenLDAP on ubuntu server but should
 * work on all other linux distributions and other LDAP implementations.
 *
 * @author Christoph Dyllick-Brenzinger (https://www.ionas-server.com/)
 * @thanks to Evert Pot for this great peace of software.
 */

class LDAP extends AbstractBasic {

    /**
     * Usage of LDAP authentification
     *
     * assuming that this file LDAP.php is saved in the directory /vendor/sabre/dav/lib/DAV/Auth/Backend/, you can add in the server.php this new LDAP authentification backend. Add the following code:
     * $ldap_settings = array(... your ldap settings ...);
     * $authBackend       = new \Sabre\DAV\Auth\Backend\LDAP($ldap_settings);
     *
     * The LDAP authentification delivers true or false depending on the provided information.
     * Currently there are no error messages if there is something wrong.
     *
     * @param array $ldap_settings
     * @return bool
     */

    /**
     * Example 1: OpenLDAP @ 127.0.0.1, plain-text communication, user in ldap tree "users",
     * Add the following code to the server.php:
     *
     * $ldap_settings = array("127.0.0.1", "ou=users,dc=ionas,dc=lan", "plain");
     * $authBackend       = new \Sabre\DAV\Auth\Backend\LDAP($ldap_settings);
     */

    /**
     * Example 2: OpenLDAP @ 192.168.45.3, STARTTLS communication, user in ldap tree "users"
     * Add the following code to the server.php:
     *
     * $ldap_settings = array("192.168.45.3", "ou=users,dc=example,dc=com");
     * $authBackend       = new \Sabre\DAV\Auth\Backend\LDAP($ldap_settings);
     */

    /**
     * Example 3: OpenLDAP @ 192.168.45.7:999, STARTTLS communication, custom search filter, user in ldap tree "users"
     * Add the following code to the server.php:
     *
     * $ldap_settings = array(
     *      "ldap_host"     => 192.168.45.7", 
     *      "ldap_basedn"   => ou=users,dc=example,dc=com", 
     *      "ldap_auth"     => starttls",
     *      "ldap_filter"   => "(&(objectclass=inetOrgPerson)(memberOf=cn=sabredav,ou=groups,dc=ionas,dc=lan))",
     *      "ldap_port"     => 999 );
     * $authBackend       = new \Sabre\DAV\Auth\Backend\LDAP($ldap_settings);
     */

    protected $ldap;
    function __construct($ldap) {
        $this->ldap = $ldap;
    }

    protected function validateUserPass($username, $password) {

        // transform $ldap_settings without keys to keys
        if ($this->ldap["ldap_host"] == "" AND $this->ldap[0] != ""){
            $this->ldap["ldap_host"] = $this->ldap[0];
            $this->ldap["ldap_basedn"] = $this->ldap[1];
            $this->ldap["ldap_auth"] = $this->ldap[2];
            $this->ldap["ldap_filter"] = $this->ldap[3];
            $this->ldap["ldap_port"] = $this->ldap[4];
            
        }

        // set default values
        if ($this->ldap["ldap_port"] == "") $this->ldap["ldap_port"] = 389;
        if ($this->ldap["ldap_auth"] == "") $this->ldap["ldap_auth"] = "starttls";

        // check connection first ( http://bugs.php.net/bug.php?id=15637 )
        $sock = @fsockopen($this->ldap["ldap_host"], $this->ldap["ldap_port"], $errno, $errstr, 1);
        @fclose($sock);

        if ($errno != 0){

            return false;
            //return [false, "No LDAP connection. Please check the host and the port."];
        }
        else{
            
            $conn = ldap_connect($this->ldap["ldap_host"], $this->ldap["ldap_port"]);
            ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);

            if ($this->ldap["ldap_auth"] != "plain") {
                if (!ldap_start_tls($conn)){

                    return false;
                    //return [false, "LDAP Connections could not be encrypted with STARTTLS. Either use ldap_auth = plain or check your STARTTLS settings"];

                }
            }
            
            $basedn_with_user = "cn=" . $username . "," . $this->ldap["ldap_basedn"];
            if ($bind = @ldap_bind($conn, $basedn_with_user, $password)){

                $filter = "cn=" . $username;
                $sr = ldap_search($conn, $this->ldap["ldap_basedn"], $filter, ["cn"]);
                $info = ldap_get_entries($conn, $sr);
                if (isset($info['count']) && $info['count'] > 0) {

                    // check the custom filter for this user
                    if ($this->ldap["ldap_filter"] != ""){

                        $sr_filter = ldap_search($conn, $basedn_with_user, $this->ldap["ldap_filter"]);
                        $info_filter = ldap_get_entries($conn, $sr_filter);
                        if (isset($info_filter['count']) && $info_filter['count'] > 0) {

                            return true;
                            // Success with ldap filter.

                        }
                        else{

                            return false;
                            //return [false, "No match with ldap filter."];

                        }

                    }

                    return true;
                    // Success with empty ldap filter.

                }

                return false;
                //return [false, "LDAP Bind successful, but no user found in the ldap database.."];

            }

            return false;
            //return [false, "LDAP Bind not possible. Please check the basedn and the password."];

        }

    }

}
