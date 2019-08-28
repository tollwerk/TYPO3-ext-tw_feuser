# TYPO3-ext-tw_user

**Version:** 0.1.0

TYPO3 Frontend and backend user tools

## Features overview

* Plugin: Frontend User - Registration
* Plugin: Frontend User - Edit profile
* Plugin: Frontend User - Change password

## Hooks

There are some useful hooks to modify the form factories and finishers.
Each hook must implement a corresponding Interface class. The advantage of
this is that all arguments and return values are well defined.

So, to see what hooks are available you just have to take a look inside */Classes/Hook*.