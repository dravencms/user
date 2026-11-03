# DravenCMS User

Authentication, authorization, user administration, groups, and ACL persistence for DravenCMS applications.

## Features

- Nette Security authenticator and authorizator.
- User identities separated by storage namespace.
- Users, companies and groups, with provider-defined ACL permissions.
- Group-based permission assignment.
- Password hashing and reset records.
- Administration forms and grids.
- Presenter traits and the `IsAllowed` PHP attribute.
- Latte `isAllowed()` function and user-name formatting.

## Installation

```bash
composer require dravencms/user
```

The package loader registers security services, permission providers, translations and Doctrine mappings. Admin presenters and components are registered only when administration is enabled. Apply the application schema before enabling authentication.

The package depends on a CAPTCHA implementation for public authentication forms and on templated email for password resets. Configure both before exposing sign-up or reset endpoints.

## Securing Presenter Actions

Use the `IsAllowed` attribute on presenter actions and signal handlers processed by DravenCMS secured presenters:

```php
use Dravencms\User\Attributes\IsAllowed;

#[IsAllowed('article', 'edit')]
public function actionEdit(?int $id = null): void
{
}
```

The request receives HTTP 403 when the authenticated user does not have the required resource/operation pair.

In Latte templates, conditionally render controls with the registered function:

```latte
<a n:if="isAllowed('article', 'edit')" n:href="Article:edit">Edit</a>
```

Use `Nette\Security\User::isAllowed()` directly inside components and services that already receive the current security user.

## Presenter Traits

- `TUserPresenter` exposes the current DravenCMS user entity, login state, and security namespace.
- `TSecuredPresenter` enforces login, initializes default user data, updates activity, and evaluates `IsAllowed` attributes.

Applications normally inherit these through DravenCMS frontend/admin base presenters rather than composing them repeatedly.

## Provider-defined permissions

User 3 defines permissions in code and stores only explicit group assignments. Implement `Dravencms\User\Acl\AclProviderInterface` and register its service from your extension when `UserExtension` is enabled:

```php
public function getPermissions(): iterable
{
    yield new AclPermission(
        resource: 'example',
        privilege: 'edit',
        label: 'example.acl.edit',
        description: 'example.acl.editDescription',
        resourceLabel: 'example.acl.resource',
        requires: ['file.edit'],
    );
}
```

The stable ID is `resource.privilege`; resource and privilege components use ASCII identifiers. Labels and descriptions are translation keys and are never copied to the database. Optional extensions can omit `resourceLabel` to inherit the owning provider's resource label. Missing prerequisites, cycles, duplicate permissions, inconsistent resource labels and case-ambiguous identifiers are configuration errors. An extension must enable the providers supplying its prerequisites.

The registry resolves transitive dependencies for both the group editor and runtime authorization. Store only explicit selections through `AclGrantStore::assign()`. Prerequisites are computed and shown as required selections. The editor's **Assign independently** action keeps a prerequisite when its parent is later removed. The server resolves dependencies independently of browser state and rejects unknown submitted IDs.

`userAclResource` stores `id` and `identifier`; `userAclOperation` stores `id`, `aclresource_id` and `identifier`. Existing group/operation foreign keys are retained. Identifier rows are materialized transactionally on first assignment; unique constraints and platform upserts prevent duplicate rows. Reading the editor or checking access never seeds ACL rows. The grant store supports MySQL/MariaDB, PostgreSQL and SQLite.

Disabled-plugin assignments remain stored but are ignored by authorization. Editing a group preserves them; they become active again if their provider returns. New standalone permissions are denied until assigned. A newly declared prerequisite becomes effective for groups already assigned its parent permission. Do not infer dependencies from operation names: current permissions retain their existing meaning unless a provider explicitly declares prerequisites.

Permission-definition CRUD routes, components and menu entries have been removed. Project-specific definitions belong in a project ACL provider. Access checks such as `isAllowed('example', 'edit')` remain unchanged.

## Migrating an existing installation

Deploy in maintenance mode, install the compatible User 3/admin/plugin releases, synchronize package configuration, and clear the compiled Nette container cache. Before serving requests with the new models, inspect and apply the targeted migration:

```bash
php bin/console user:acl:migrate
php bin/console user:acl:migrate --apply
```

The migration explicitly renames `name` to `identifier`, removes descriptions and timestamp columns from the two ACL definition tables, and ensures identifier uniqueness. IDs, identifiers, memberships and group grants are preserved; existing grants remain explicit. The command is idempotent and rejects duplicate/orphan identifiers or unexpected columns before applying DDL. Back up the database before applying it: removed descriptive metadata cannot be restored without a backup. MySQL DDL is not transactional. The data-preservation/FK tests run on SQLite; review the generated plan against your production database.

Port custom permission definitions into a project provider before migration. Unknown grants remain stored but inactive. Remove any configuration overrides for the retired `user.acl` menu entry. Do not use a generic schema update as a substitute for the explicit identifier rename.

No ACL definitions or grants are installed by fixtures, and no example user/group fixtures are registered.

## Initial administrator and recovery

For a new installation, create the schema and explicitly provision the initial account:

```bash
php bin/console user:acl:bootstrap admin@example.com
```

A new account prompts for its password without echoing it; automation can supply `--password-stdin`. Existing account passwords are not changed. The default grant is `user.edit`, which permits managing users, groups and their permissions. `--group` selects the group. `--all` explicitly grants every currently registered permission once; it does not automatically grant future permissions. The command also recovers access for an existing account after its management grant was removed.

## License

This package is licensed under the LGPL-3.0 license.

## Session and password-reset security (User 3.1)

Deploy with Admin 3.2 and the compatible plugin releases in maintenance mode. Clear the compiled container cache, preview and apply the targeted reset-digest migration before serving requests:

```sh
php bin/console user:security:migrate
php bin/console user:security:migrate --apply
```

Only `userPasswordReset.hash` is widened from 32 to 64 characters. IDs and other data are retained. Back up the database and review the generated SQL; MySQL DDL is not transactional. Migration data-preservation tests run on SQLite.

New reset tokens use 32 random bytes and persist only a SHA-256 digest. Existing reset links are invalidated by this upgrade; users can request a new link. Expiry and single use are rechecked in the password-change transaction. Successful consumption invalidates other outstanding resets. Reset passwords must contain at least eight characters after trimming.

Session identities include their security namespace and a password fingerprint. Old session identities require a fresh sign-in. Disabled/deleted accounts, wrong namespaces and password changes invalidate restored sessions. Password changes also require the current browser to sign in again.

Authenticated activity is recorded at most once per user per 300 seconds using a conditional update, without flushing unrelated managed entities. Configure `user.activityInterval` in seconds (minimum 1) to change this interval. Login-time default-data creation and the empty DefaultDataCreator service are removed; the legacy initialization column is retained to avoid an unrelated schema migration.
