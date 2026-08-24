# DravenCMS User

Authentication, authorization, user administration, groups, and ACL persistence for DravenCMS applications.

## Features

- Nette Security authenticator and authorizator.
- User identities separated by storage namespace.
- Users, companies, groups, ACL resources, and ACL operations.
- Group-based permission assignment.
- Password hashing and reset records.
- Administration forms and grids.
- Presenter traits and the `IsAllowed` PHP attribute.
- Latte `isAllowed()` function and user-name formatting.

## Installation

```bash
composer require dravencms/user
```

The package loader registers security services, admin presenters, components, translations, fixtures, and Doctrine mappings. Apply the package schema before enabling authentication.

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

## Fixtures and Default Credentials

The bundled fixtures create the Administrator group and a development account using `admin@example.com` / `adminExample` in both Front and Admin namespaces.

These credentials are public and must never remain usable in a deployed application. Replace or disable the fixture account immediately, or provide project-specific user fixtures before production deployment.

## License

This package is licensed under the LGPL-3.0 license.
