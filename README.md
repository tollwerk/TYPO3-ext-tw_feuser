# TYPO3-ext-tw_user

**Version:** 0.1.0

TYPO3 Frontend and backend user tools

## Features overview

* Frontend User Registration

## Hooks

### beforeBuildingFinished

Use this to modify the form definition for the FrontendUser registration form. This hook is called right before the RegistrationFormFactory calls the `triggerFormBuildingFinished()` method.

#### Connect to the hook

```php
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/tw_user']['beforeBuildingFinished'][<useATimestampAsKeyPlease>] = \VENDOR\YourNamespace\YourClass::class;
```


## Troubleshooting

### cHash error on form submissions

Can be solved by creating a valid site configuraton in the TYPO3 backend.
