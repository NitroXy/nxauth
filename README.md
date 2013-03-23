API for nitroxy.com
==================

This code provide a API for nitroxy.com.
On a basic level it provides cas login, giving you only username and name of the user,
but with a API key you can gain access to much more.

Usage
=======
Put this as a submodule in your repository, then copy nxauth.sample.php into your project and modify it.

Functions
--------

### NXAuth

_These functions does not require a API key_

* NXAuth::login(): Trigger login
* NXAuth::logout(): Trigger logout
* NXAuth::is_authenticated(): bool
* NXAuth::user(): Return a NXUser instance with the current user, or null

### NXUser

_These functions does not require a API key_

Contains these attributes:
* username
* user_id
* fullname
* ticket

### NXAPI

NXAPI::api_function_name(api_options) calls api_function_name with api_options.
