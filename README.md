# DstRoot
Replicates Let's Encrypt certificate chains to test DST_ROOT_CA_X3 expiry with PHP clients.

## About





### Compatibility table


| PHP version | OpenSSL version      | DST_ROOT_CA_X3 | ISRG_ROOT_X1 |
|-------------|----------------------|----------------|--------------|
|             | 1.0.2a (19 Mar 2015) | Success*       | Success*     |
| <= 5.6.10   | 1.0.1m (19 Mar 2015) | Fail           | Fail         |
| >= 5.6.11   | 1.0.1p (15 Jul 2015) | Fail           | Success      |
| >= 7.2.0    | 1.1.0g (02 Nov 2017) | Success        | Success      |

\* not tested


### Example client output

#### PHP-5.6.10 default chain
```sh
PHP Version: 5.6.10
OpenSSL version: OpenSSL 1.0.1m 19 Mar 2015
cURL version: 7.42.1, ssl OpenSSL/1.0.1m

streams (bundle_both.crt)
Error:14090086:SSL routines:SSL3_GET_SERVER_CERTIFICATE:certificate verify failed
FAILED

curl (bundle_both.crt)
Peer certificate cannot be authenticated with given CA certificates
FAILED

streams (bundle_root.crt)
Error:14090086:SSL routines:SSL3_GET_SERVER_CERTIFICATE:certificate verify failed
FAILED

curl (bundle_root.crt)
Peer certificate cannot be authenticated with given CA certificates
FAILED
```


#### PHP-5.6.10 alt chain
```sh
$ /c/php-5.6.10/php.exe client.php
PHP Version: 5.6.10
OpenSSL version: OpenSSL 1.0.1m 19 Mar 2015
cURL version: 7.42.1, ssl OpenSSL/1.0.1m

streams (bundle_both.crt)
Response from certchain.testing.org
SUCCESS

curl (bundle_both.crt)
Response from certchain.testing.org
SUCCESS

streams (bundle_root.crt)
Response from certchain.testing.org
SUCCESS

curl (bundle_root.crt)
Response from certchain.testing.org
SUCCESS
```

