CREATE TABLE acl ( 
    uri text, 
    principal text,
    special bool, 

    read bool, 
    readacl bool, 
    writecontent bool, 
    writeprops bool, 
    writeacl bool, 
    bind bool, 
    unbind bool, 
    unlock bool
);
