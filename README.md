# Roundcube Postfix Virtual User Mapping

Add in your config file:

```php
$config['postfixVirtualMapPath'] = "/etc/postfix/virtual";
 ```

Now you can login with "user@domain.com" instead of "user", as defined in virtual users table in Postfix.

Note: This is reading from the text file, not from Postfix's optimized .db file.
