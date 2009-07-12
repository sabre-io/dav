#!/usr/bin/env python

import os
import shutil

def main() :
    print "Setting up SabreDAV fileserver"
    os.mkdir('public')
    os.mkdir('tmpdata')
    shutil.copyfile('examples/fileserver.php','fileserver.php');
    print "Warning: setting public/ and tmpdata/ as world-writeable (777)"
    os.chmod('public',0777);
    os.chmod('tmpdata',0777);
    print "Creating .htaccess and .htdigest files"
    file = open('.htaccess','w')
    file.write("""
RewriteEngine On
RewriteBase /
RewriteRule ^(.*)$ fileserver.php [L,QSA]
""")
    file.close()

    print "Warning: default username and password is admin / admin"
    file = open('.htdigest','w')
    file.write("admin:SabreDAV:87fd274b7b6c01e48d7c2f965da8ddf7")
    file.close()
    print "Setup complete, now edit RewriteBase in .htaccess and $baseUri in fileserver.php if your baseUri is something else than the root." 

if __name__ == "__main__" :
    main()
