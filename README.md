# TYPO3-ext-tw_user

**Version:** 0.2.0

TYPO3 Frontend and backend user tools





## Features overview

* Frontend User Registration





## Hooks

When executing hooks tw_user always requires the registered classes to implement an interface. The intention is to always provide a well defined behavior when registering hooks for this extension.

For example, when you want to use the 'frontendUserRegistration' hook, you have to include this code inside the *ext_localconf.php* of your own extension:

```php
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/tw_user']['frontendUserRegistration'][<useATimestampAsKeyPlease>] = \VENDOR\YourNamespace\YourClass::class;
```

As mentioned above, your class must implement the right interface and the corresponding methods:

```php
class YourClass implements \Tollwerk\TwUser\Hook\FrontendUserHookInterface {
    
    public function frontendUserRegistration(string &$status = null, array &$passthrough = null, array &$form = null) {
        // TODO: Implement something or leave empty 
    }
    
    public function frontendUserConfirmRegistration(string &$code, FrontendUser $frontendUser = null): void {
        // TODO: Implement something or leave empty 
    }
    
    public function frontendUserRegistrationForm(FormDefinition $form): void {
        // TODO: Implement something or leave empty 
    }
}
```



### frontendUserRegistrationForm

**Interface:** \Tollwerk\TwUser\Hook\FrontendUserHookInterface

Gets called when building the FrontendUser registration form. Can be used to manipulate the FormDefinition, for example, to add more form fields, pages, validators etc.



### frontendUserRegistration

**Interface:** \Tollwerk\TwUser\Hook\FrontendUserHookInterface

Gets called inside `Controller/FrontendUser->registrationAction()` and can be used to manipulate all parameters before rendering the registration form or handling the different statuses. Also use this to redirect to other pages and plugins. This comes in handy when the user registration is part of a larger workflow. 



### frontendUserConfirmRegistration

**Interface:** \Tollwerk\TwUser\Hook\FrontendUserHookInterface

Gets called after trying to find a FrontendUser for the given confirmation code but before changing anything on that record.





## Troubleshooting

### cHash error on form submissions

Can be solved by creating a valid site configuraton in the TYPO3 backend.
