This document's format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).


## Unreleased - hardening

Security and PHP 8 fixes. No API changes.

### Behaviour changes to be aware of

- **Listeners and handlers now run.** Listeners added with `Node::addListener()` and handlers registered with
  `Atk::atkRegisterNodeHandler()` were silently ignored since the move to namespaces. They are active again.
- **`.env` values are now applied.** `Config::env()` and `getenv()` did not see values loaded from `.env`
  (only real environment variables). They do now, so check that `.env` holds what you expect.
- **MySQL errors are reported through ATK again** instead of being thrown as `mysqli_sql_exception`.
- **CSRF tokens** are stored in the session only. Forms that were open during the upgrade must be reloaded.
- **Sessions** get a new id at login; the session cookie is `HttpOnly`, `SameSite=Lax` and `Secure` on HTTPS.
  The password hash is no longer kept in the session user record.
- Unknown search modes, non-numeric paging values and non-column export orderings are ignored.

### New configuration

- `where_clause_validation` (`off`, `log`, `enforce`; default `log`, env `WHERE_CLAUSE_VALIDATION`):
  validates `atkselector`, `atkfilter` and `atkprimkey` from the request against a strict grammar.
  Run with `log`, review the PHP error log for `ATK where clause validation` lines, then set `enforce`.
- `select_action_privilege` (default empty): set to a privilege name such as `admin` to stop every logged-in user
  from listing any node through the `select` action.

### Recommended production settings

`authentication` and `securityscheme` not `none`, `display_errors` `false`, `debug` `0`,
`where_clause_validation` `enforce`, `select_action_privilege` set.

## [9.3] - 2022
Contains mainly bugfixes and small changes. 

## [9.2] - 2021
This branch starts from v9.1 and it contains an updated style based on AdminLte framework. 
We are making other changes that will be rolled out in the future, generally we will be work 
on fixing existing functionalities on the version 9 line.

## [9.1] - 2018
Removed all custom javascript and added JQuery. 