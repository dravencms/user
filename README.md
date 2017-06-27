# Dravencms User module

This is a User module for dravencms

## Instalation

The best way to install dravencms/user is using  [Composer](http://getcomposer.org/):


```sh
$ composer require dravencms/user:@dev
```

Then you have to register extension in `config.neon`.

```yaml
extensions:
	user: Dravencms\User\DI\UserExtension
	doctrine2identity: Majkl578\NetteAddons\Doctrine2Identity\DI\IdentityExtension
```
