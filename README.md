# DstRoot
Replicates Let's Encrypt certificate chains to test DST ROOT CA X3 expiry with PHP clients.

## About
The Iden Trust DST ROOT CA X3 will/has expire(d) on 30th September 2021. This repo was created
before this date to check the effect on PHP clients.

The Let's Encrypt default certificate chain deliberately uses DST ROOT CA X3 to cross-sign its own
ISRG Root X1. The reason is to limit breakage on older Android systems (< 7.1.1) that do not
receive root certificate updates, where it transpires that the expiry date of the CA root is not
actually checked.

For other clients to work with this Android-compatible certificate chain, the self-signed ISRG Root
X1 must be in the trust store and clients must recognize and use it to verify the chain.

Unfortunately OpenSSL versions 1.0.2 and below do not have this capability and because OpenSSL is
used natively in PHP streams and very often in PHP curl, connections to servers with the Let's
Encrypt default certificate chain will fail.

* https://www.openssl.org/blog/blog/2021/09/13/LetsEncryptRootCertExpire/
* https://community.letsencrypt.org/t/openssl-client-compatibility-changes-for-let-s-encrypt-certificates/143816

The Let's Encrypt alternate certificate chain does not cause these problems, though it will fail if
the self-signed ISRG Root X1 is not available.

### Contents

* [Getting started](#getting-started)
* [Configuration](#configuration)
* [Testing PHP](#testing-php)
* [Compatibility table](#compatibility-table)

## Getting started

Download or clone this repo.

```
$ git clone https://github.com/johnstevenson/dstroot.git
$ cd dstroot
```

## Configuration

### Steps
1. Build the certificates, chains and bundles.

```
$ php chain.php
```
2. Edit your hosts file to add the `dstroot.testing.org` domain. For example:

```
127.0.0.1 dstroot.testing.org
```
3. Configure your web server to serve `path/to/dstroot/index.php` from a request to
`https://dstroot.testing.org`. For example (Apache):

```
<VirtualHost *:443>
    SSLEngine On
    ServerName dstroot.testing.org
    DocumentRoot "path/to/dstroot"
    SSLCertificateFile "path/to/dstroot/certs/server_def_chain.crt"
    SSLCertificateKeyFile "path/to/dstroot/certs/server.key"
</VirtualHost>
```

### Certificates chains and bundles
All certificates are in the `certs` folder.

Two server certificate chains are created:
1. `server_def_chain.crt` is the default chain.
2. `server_alt_chain.crt` is the alternate chain.

Two client ca-bundles are created:
1. `bundle_both.crt` contains DST ROOT CA X3 and ISRG ROOT X1.
2. `bundle_root.crt` contains only ISRG ROOT X1.

Use openssl to view the certificate chain:

```
openssl s_client -connect dstroot.testing.org:443
---
Certificate chain
 0 s:C = US, O = DstRoot Testing Org, CN = dstroot.testing.org
   i:C = US, O = Not Let's Encrypt, CN = Mock R3
 1 s:C = US, O = Not Let's Encrypt, CN = Mock R3
   i:C = US, O = Not Internet Security Research Group, CN = Mock ISRG Root X1
 2 s:C = US, O = Not Internet Security Research Group, CN = Mock ISRG Root X1
   i:C = US, O = Not Digital Signature Trust Co, CN = Mock DST Root CA X3
---
```
Note that there will be an _"unable to get local issuer certificate_" error in the above output
because openssl cannot find `Mock DST Root CA X3`. This can be fixed by using the `bundle_both.crt`
client bundle:

```
openssl s_client -CAfile certs/bundle_both.crt -connect dstroot.testing.org:443
```

To view the alternate chain, set the server certificate to `server_alt_chain.crt` and restart the
web server.

```
openssl s_client -connect dstroot.testing.org:443
---
Certificate chain
 0 s:C = US, O = DstRoot Testing Org, CN = dstroot.testing.org
   i:C = US, O = Not Let's Encrypt, CN = Mock R3
 1 s:C = US, O = Not Let's Encrypt, CN = Mock R3
   i:C = US, O = Not Internet Security Research Group, CN = Mock ISRG Root X1
---
```

## Testing PHP
Run `client.php` to test different PHP versions. The script sends a request to
`https://dstroot.testing.org` using PHP streams and PHP curl, reporting either FAIL or SUCCESS.

Remember to restart the web server when switching between the default and alternate chains.

### Example output

Older PHP versions fail with the default chain (`server_def_chain.crt`).

#### PHP-5.6.10 default chain
```sh
$ /c/php-5.6.10/php.exe client.php
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

Older PHP versions work with the alternate chain (`server_alt_chain.crt`).

#### PHP-5.6.10 alternate chain

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

## Compatibility table
This table shows which versions of Windows PHP work with the Let's Encrypt default certificate
chain. The Fail/Success columns relate to the CA certificates on the client.


| Windows<br>PHP version | OpenSSL version | DST_ROOT_CA_X3<br>ISRG_ROOT_X1 | ISRG_ROOT_X1 |
|-------------|----------------------|---------|---------|
| <= 5.6.10   | 1.0.1m (19 Mar 2015) | Fail    | Fail    |
| >= 5.6.11   | 1.0.1p (15 Jul 2015) | Fail    | Success |
| >= 7.2.0    | 1.1.0g (02 Nov 2017) | Success | Success |

Because Windows builds use a fixed OpenSSL version for each release (which is not updated, unlike
distro builds), this table represents a worst case scenario.
